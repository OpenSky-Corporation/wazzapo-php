<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use PHPUnit\Framework\TestCase;
use Wazzapo\Exception\ConnectionException;
use Wazzapo\Exception\RateLimitException;
use Wazzapo\Exception\ServerException;
use Wazzapo\Wazzapo;

final class ClientTest extends TestCase
{
    /** @var list<float> */
    private array $sleeps = [];

    private function client(FakeTransport $t, int $retries = 2): Wazzapo
    {
        return new Wazzapo([
            'api_key' => 'wz_live_1234567890abcdef',
            'transport' => $t,
            'max_retries' => $retries,
            'sleep' => function (float $s): void {
                $this->sleeps[] = $s;
            },
        ]);
    }

    private const SEND = ['phone_number_id' => '123', 'to' => '77071234567', 'type' => 'text', 'text' => ['body' => 'Привет']];

    public function testSendBuildsRequest(): void
    {
        $t = new FakeTransport([FakeTransport::json(200, ['message_id' => 'wamid.1', 'status' => 'accepted'])]);
        $out = $this->client($t)->messages->send(self::SEND);
        self::assertSame('wamid.1', $out['message_id']);
        $r = $t->requests[0];
        self::assertSame('POST', $r->method);
        self::assertSame('https://wazzapo.kz/api/v2/external/messages/send', $r->url);
        self::assertSame('wz_live_1234567890abcdef', $r->headers['X-API-Key']);
        self::assertStringStartsWith('wazzapo-sdk-php/0.1.0', $r->headers['User-Agent']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $r->headers['Idempotency-Key']);
        self::assertSame(self::SEND, json_decode($r->body, true));
    }

    public function testOptionsAndFiltersShareLastArray(): void
    {
        // Настройки вызова не уходят в адрес: новый фильтр в API не ломает такой вызов
        $t = new FakeTransport([FakeTransport::json(200, ['data' => []])]);
        $this->client($t)->phoneNumbers->list(['channel' => 'whatsapp', 'timeout' => 5, 'headers' => ['X-Request-Id' => 'r1']]);
        $r = $t->requests[0];
        self::assertSame('https://wazzapo.kz/api/v2/external/phone-numbers?channel=whatsapp', $r->url);
        self::assertSame('r1', $r->headers['X-Request-Id']);
    }

    public function testRetriesKeepSameIdempotencyKey(): void
    {
        $t = new FakeTransport([
            FakeTransport::json(503, ['success' => false, 'error' => ['code' => 'SERVICE_UNAVAILABLE', 'message' => 'x']]),
            new ConnectionException('сеть'),
            FakeTransport::json(200, ['message_id' => 'wamid.2']),
        ]);
        $this->client($t)->messages->send(self::SEND);
        self::assertCount(3, $t->requests);
        $keys = array_unique(array_map(fn ($r) => $r->headers['Idempotency-Key'], $t->requests));
        self::assertCount(1, $keys);
        self::assertCount(2, $this->sleeps);
        self::assertGreaterThanOrEqual(0.375, $this->sleeps[0]);
        self::assertLessThanOrEqual(0.625, $this->sleeps[0]);
    }

    public function testOwnIdempotencyKey(): void
    {
        $t = new FakeTransport([FakeTransport::json(200, ['message_id' => 'w'])]);
        $this->client($t)->messages->send(self::SEND, ['idempotency_key' => 'order-42']);
        self::assertSame('order-42', $t->requests[0]->headers['Idempotency-Key']);
    }

    public function testRetryAfterIsRespected(): void
    {
        $limited = FakeTransport::json(429, ['success' => false, 'error' => ['code' => 'RATE_LIMIT_EXCEEDED', 'message' => 'x']], ['Retry-After' => '7']);
        $t = new FakeTransport([$limited, FakeTransport::json(200, ['data' => []])]);
        $this->client($t)->phoneNumbers->list();
        self::assertSame([7.0], $this->sleeps);
    }

    public function testRetryAfterCappedAt60(): void
    {
        $limited = FakeTransport::json(429, ['success' => false, 'error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '600']);
        $t = new FakeTransport([$limited, FakeTransport::json(200, ['data' => []])]);
        $this->client($t)->phoneNumbers->list();
        self::assertSame([60.0], $this->sleeps);
    }

    public function testGivesUpAfterMaxRetries(): void
    {
        $limited = FakeTransport::json(429, ['success' => false, 'error' => ['code' => 'RATE_LIMIT_EXCEEDED', 'message' => 'x']], ['Retry-After' => '1']);
        $t = new FakeTransport([$limited, $limited, $limited]);
        try {
            $this->client($t)->phoneNumbers->list();
            self::fail('нет исключения');
        } catch (RateLimitException $e) {
            self::assertSame(1, $e->retryAfter());
        }
        self::assertCount(3, $t->requests);
    }

    public function testNoRetryOn4xx(): void
    {
        $t = new FakeTransport([FakeTransport::json(404, ['success' => false, 'error' => ['code' => 'contact_not_found', 'message' => 'x']])]);
        $this->expectException(\Wazzapo\Exception\NotFoundException::class);
        try {
            $this->client($t)->contacts->get('c1');
        } finally {
            self::assertCount(1, $t->requests);
        }
    }

    public function testConnectionErrorAfterRetries(): void
    {
        $t = new FakeTransport([new ConnectionException('a'), new ConnectionException('b')]);
        $this->expectException(ConnectionException::class);
        $this->client($t, 1)->phoneNumbers->list();
    }

    public function testServerErrorAfterRetries(): void
    {
        $e500 = FakeTransport::json(500, ['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'x'], 'request_id' => 'r1']);
        $t = new FakeTransport([$e500]);
        try {
            $this->client($t, 0)->phoneNumbers->list();
            self::fail('нет исключения');
        } catch (ServerException $e) {
            self::assertSame('r1', $e->requestId);
            self::assertSame(500, $e->status);
        }
    }

    public function testQueryAndPathEncoding(): void
    {
        $t = new FakeTransport([FakeTransport::json(200, ['data' => [], 'has_more' => false, 'next_cursor' => null])]);
        $this->client($t)->conversations->listMessages('a/b', ['include_notes' => true, 'limit' => 10, 'since' => null]);
        self::assertSame('https://wazzapo.kz/api/v2/external/conversations/a%2Fb/messages?include_notes=true&limit=10', $t->requests[0]->url);
    }

    public function testDeleteReturnsNull(): void
    {
        $t = new FakeTransport([FakeTransport::json(204, null)]);
        self::assertNull($this->client($t)->contacts->delete('c1'));
        self::assertSame('DELETE', $t->requests[0]->method);
        self::assertArrayNotHasKey('Idempotency-Key', $t->requests[0]->headers);
    }

    public function testPaginationWalksAllPages(): void
    {
        $t = new FakeTransport([
            FakeTransport::json(200, ['data' => [['id' => '1'], ['id' => '2']], 'has_more' => true, 'next_cursor' => 'c2']),
            FakeTransport::json(200, ['data' => [['id' => '3']], 'has_more' => false, 'next_cursor' => null]),
        ]);
        $ids = [];
        foreach ($this->client($t)->contacts->listAll(['limit' => 2]) as $contact) {
            $ids[] = $contact['id'];
        }
        self::assertSame(['1', '2', '3'], $ids);
        self::assertStringContainsString('cursor=c2', $t->requests[1]->url);
        self::assertStringContainsString('limit=2', $t->requests[1]->url);
    }

    public function testRequiredQueryParamIsPositional(): void
    {
        $t = new FakeTransport([FakeTransport::json(200, ['data' => [], 'has_more' => false])]);
        $this->client($t)->templates->list('123', ['status' => 'APPROVED']);
        self::assertSame('https://wazzapo.kz/api/v2/external/templates?phone_number_id=123&status=APPROVED', $t->requests[0]->url);
    }

    public function testMediaUploadAndDownload(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'wz');
        file_put_contents($file, 'PDF');
        $t = new FakeTransport([
            FakeTransport::json(200, ['media_id' => 'm1']),
            new \Wazzapo\Transport\Response(200, ['content-type' => 'application/pdf'], '%PDF-bytes'),
        ]);
        $client = $this->client($t);
        self::assertSame('m1', $client->media->upload('123', $file)['media_id']);
        self::assertInstanceOf(\CURLFile::class, $t->requests[0]->body['file']);
        self::assertArrayNotHasKey('Content-Type', $t->requests[0]->headers);
        self::assertSame('%PDF-bytes', $client->media->download('m1', '123'));
        unlink($file);
    }

    public function testKeyIsMasked(): void
    {
        $client = $this->client(new FakeTransport([]));
        $dump = print_r($client, true) . print_r($client->http, true);
        self::assertStringNotContainsString('1234567890abcdef', $dump);
        self::assertStringContainsString('wz_live_…', $dump);
        self::assertFalse($client->isTest());
    }

    public function testTestKey(): void
    {
        $client = new Wazzapo(['api_key' => 'wz_test_abc', 'transport' => new FakeTransport([])]);
        self::assertTrue($client->isTest());
    }

    public function testMissingKey(): void
    {
        putenv('WAZZAPO_API_KEY');
        $this->expectException(\Wazzapo\Exception\WazzapoException::class);
        $this->expectExceptionMessage('Нет API-ключа');
        new Wazzapo(['transport' => new FakeTransport([])]);
    }

    public function testFalseKeyFromGetenvGivesClearError(): void
    {
        putenv('WAZZAPO_API_KEY');
        try {
            new Wazzapo(['api_key' => getenv('WAZZAPO_API_KEY'), 'transport' => new FakeTransport([])]);
            self::fail('нет ошибки');
        } catch (\Wazzapo\Exception\WazzapoException $e) {
            self::assertSame('MISSING_API_KEY', $e->errorCode);
        }
    }

    public function testKeyHiddenInVarExportAndSerialize(): void
    {
        $client = $this->client(new FakeTransport([]));
        $dump = var_export($client, true) . var_export($client->http, true);
        self::assertStringNotContainsString('1234567890abcdef', $dump);
        $this->expectException(\LogicException::class);
        serialize($client);
    }

    public function testUploadMissingFileFailsBeforeRequest(): void
    {
        $t = new FakeTransport([]);
        try {
            $this->client($t)->media->upload('123', '/нет/такого/файла.pdf');
            self::fail('нет ошибки');
        } catch (\Wazzapo\Exception\WazzapoException $e) {
            self::assertSame('FILE_NOT_FOUND', $e->errorCode);
        }
        self::assertCount(0, $t->requests);
    }

    public function testRetryAfterAsHttpDate(): void
    {
        $date = gmdate('D, d M Y H:i:s', time() + 5) . ' GMT';
        $t = new FakeTransport([
            FakeTransport::json(429, ['success' => false, 'error' => ['code' => 'RATE_LIMIT_EXCEEDED', 'message' => 'x']], ['Retry-After' => $date]),
            FakeTransport::json(200, ['data' => [], 'has_more' => false]),
        ]);
        $this->client($t)->contacts->list();
        self::assertGreaterThanOrEqual(3.0, $this->sleeps[0]);
        self::assertLessThanOrEqual(6.0, $this->sleeps[0]);
    }

    public function testRepeatedCursorStopsPagination(): void
    {
        $page = ['data' => [['id' => 'c1']], 'has_more' => true, 'next_cursor' => 'same'];
        $t = new FakeTransport([FakeTransport::json(200, $page), FakeTransport::json(200, $page), FakeTransport::json(200, $page)]);
        $seen = 0;
        try {
            foreach ($this->client($t)->contacts->listAll() as $item) {
                $seen++;
            }
            self::fail('обход не остановился');
        } catch (\Wazzapo\Exception\WazzapoException $e) {
            self::assertSame('PAGINATION_LOOP', $e->errorCode);
        }
        self::assertSame(2, $seen);
        self::assertCount(2, $t->requests);
    }
}
