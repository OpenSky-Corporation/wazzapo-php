<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wazzapo\Exception;
use Wazzapo\Exception\ErrorFactory;
use Wazzapo\Generated\ErrorCodes;
use Wazzapo\Transport\Response;

final class ErrorsTest extends TestCase
{
    private const CLASSES = [
        400 => Exception\BadRequestException::class,
        401 => Exception\AuthenticationException::class,
        402 => Exception\PaymentRequiredException::class,
        403 => Exception\PermissionDeniedException::class,
        404 => Exception\NotFoundException::class,
        409 => Exception\ConflictException::class,
        413 => Exception\ApiException::class,
        422 => Exception\ValidationException::class,
        429 => Exception\RateLimitException::class,
        500 => Exception\ServerException::class,
        501 => Exception\ServerException::class,
        502 => Exception\ServerException::class,
        503 => Exception\ServerException::class,
    ];

    /** Примеры ответов с ошибкой из описания API: по одному на статус. */
    public static function examples(): iterable
    {
        $spec = json_decode((string) file_get_contents(__DIR__ . '/openapi.json'), true);
        $seen = [];
        foreach ($spec['paths'] as $path => $item) {
            foreach ($item as $op) {
                foreach ($op['responses'] as $status => $resp) {
                    $example = $resp['content']['application/json']['example'] ?? null;
                    if ($status >= 400 && $example && !isset($seen[$status])) {
                        $seen[$status] = true;
                        yield "HTTP $status" => [(int) $status, $example];
                    }
                }
            }
        }
    }

    #[DataProvider('examples')]
    public function testExampleMapsToClass(int $status, array $example): void
    {
        $e = ErrorFactory::fromResponse(new Response($status, [], json_encode($example)));
        self::assertInstanceOf(self::CLASSES[$status], $e);
        self::assertSame($example['error']['code'], $e->errorCode);
        self::assertSame($example['error']['message'], $e->getMessage());
        self::assertSame($example['request_id'], $e->requestId);
        self::assertSame($status, $e->status);
        self::assertArrayHasKey($e->errorCode, ErrorCodes::STATUS);
    }

    public function testSpecialCodes(): void
    {
        $make = fn (int $s, string $c, array $d = []) => ErrorFactory::fromResponse(
            new Response($s, [], json_encode(['success' => false, 'error' => ['code' => $c, 'message' => 'm', 'details' => $d]]))
        );
        self::assertInstanceOf(Exception\OutsideWindowException::class, $make(422, 'outside_24h_window'));
        self::assertInstanceOf(Exception\TemplateException::class, $make(422, 'template_paused'));
        $usage = $make(403, 'USAGE_LIMIT_EXCEEDED', ['current' => 1000, 'limit' => 1000]);
        self::assertInstanceOf(Exception\UsageLimitException::class, $usage);
        self::assertSame(1000, $usage->details['limit']);
        self::assertInstanceOf(Exception\UnprocessableEntityException::class, $make(422, 'invalid_parameters'));
    }

    public function testNotJsonBody(): void
    {
        $e = ErrorFactory::fromResponse(new Response(502, ['x-request-id' => 'abc'], '<html>Bad Gateway</html>'));
        self::assertInstanceOf(Exception\ServerException::class, $e);
        self::assertSame('abc', $e->requestId);
        // Ответ не в формате API: код по статусу из таблицы сервера, не пустой
        self::assertSame('BAD_GATEWAY', $e->errorCode);
    }

    public function testBadRequestAndUnprocessableHierarchy(): void
    {
        $make = fn (int $s, string $c) => ErrorFactory::fromResponse(
            new Response($s, [], json_encode(['success' => false, 'error' => ['code' => $c, 'message' => 'm']]))
        );
        self::assertInstanceOf(Exception\BadRequestException::class, $make(400, 'BAD_REQUEST'));
        foreach (['VALIDATION_ERROR', 'outside_24h_window', 'template_paused', 'invalid_parameters'] as $code) {
            self::assertInstanceOf(Exception\UnprocessableEntityException::class, $make(422, $code), $code);
        }
    }

    public function testValidationFields(): void
    {
        $e = ErrorFactory::fromResponse(new Response(422, [], json_encode(['success' => false, 'error' => [
            'code' => 'VALIDATION_ERROR', 'message' => 'Request validation failed',
            'details' => [['field' => 'body.to', 'message' => 'Field required']],
        ]])));
        self::assertInstanceOf(Exception\ValidationException::class, $e);
        self::assertSame([['field' => 'body.to', 'message' => 'Field required']], $e->fields());
    }

    public function testNotApiFormatGetsCodeByStatus(): void
    {
        $e = ErrorFactory::fromResponse(new Response(404, [], '<html>Not Found</html>'));
        self::assertInstanceOf(Exception\NotFoundException::class, $e);
        self::assertSame('NOT_FOUND', $e->errorCode);
        $e = ErrorFactory::fromResponse(new Response(418, [], ''));
        self::assertSame('HTTP_418', $e->errorCode);
    }

    public function testConnectionAndTimeoutCodes(): void
    {
        self::assertSame('CONNECTION_ERROR', (new Exception\ConnectionException('нет сети'))->errorCode);
        self::assertSame('TIMEOUT', (new Exception\TimeoutException('долго'))->errorCode);
    }

    public function testRetryAfterHttpDate(): void
    {
        $e = ErrorFactory::fromResponse(new Response(429, ['retry-after' => '7'], '{}'));
        self::assertInstanceOf(Exception\RateLimitException::class, $e);
        self::assertSame(7, $e->retryAfter());
        self::assertSame(30.0, \Wazzapo\HttpClient::parseRetryAfter('Sun, 19 Oct 2026 08:00:30 GMT', strtotime('Sun, 19 Oct 2026 08:00:00 GMT')));
        self::assertNull(\Wazzapo\HttpClient::parseRetryAfter('потом'));
    }
}
