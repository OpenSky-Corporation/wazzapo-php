<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use PHPUnit\Framework\TestCase;
use Wazzapo\Exception\WazzapoException;
use Wazzapo\Exception\WebhookVerificationException;
use Wazzapo\Webhooks;

final class WebhooksTest extends TestCase
{
    private const BODY = '{"event":"message.incoming","timestamp":"2026-10-19T10:00:00Z","data":{"from":"77071234567"}}';
    private const SECRET = 'whsec_test';

    public function testValidSignature(): void
    {
        $headers = Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000);
        $event = Webhooks::verify(self::BODY, $headers, self::SECRET, 300, 1_760_000_100);
        self::assertSame('message.incoming', $event['event']);
        self::assertSame('77071234567', $event['data']['from']);
    }

    public function testMatchesServerFormula(): void
    {
        // Как в backend/app/services/webhook_delivery_service.py::_sign_v2
        $expected = 'v2=' . hash_hmac('sha256', '1760000000.' . self::BODY, self::SECRET);
        self::assertSame($expected, Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000)['X-Wazzapo-Signature-V2']);
    }

    public function testHeaderCaseDoesNotMatter(): void
    {
        $h = Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000);
        $lower = ['x-wazzapo-signature-v2' => [$h['X-Wazzapo-Signature-V2']], 'x-wazzapo-timestamp' => $h['X-Wazzapo-Timestamp']];
        self::assertSame('message.incoming', Webhooks::verify(self::BODY, $lower, self::SECRET, 300, 1_760_000_000)['event']);
    }

    public function testWrongSecret(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhooks::verify(self::BODY, Webhooks::sign(self::BODY, 'other', 1_760_000_000), self::SECRET, 300, 1_760_000_000);
    }

    public function testTamperedBody(): void
    {
        $this->expectException(WazzapoException::class);
        Webhooks::verify(str_replace('7707', '7700', self::BODY), Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000), self::SECRET, 300, 1_760_000_000);
    }

    public function testExpiredTimestamp(): void
    {
        $this->expectExceptionMessage('вне допуска');
        Webhooks::verify(self::BODY, Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000), self::SECRET, 300, 1_760_000_301);
    }

    public function testMissingHeaders(): void
    {
        $this->expectException(WazzapoException::class);
        Webhooks::verify(self::BODY, [], self::SECRET);
    }

    /** @return iterable<string, array{mixed}> */
    public static function emptySecrets(): iterable
    {
        yield 'пустая строка' => [''];
        yield 'пробелы' => ['  '];
        yield 'getenv без переменной' => [false];
        yield 'null' => [null];
    }

    /** Тело, подписанное пустым ключом, не должно пройти, когда секрет не задан (README: getenv). */
    #[\PHPUnit\Framework\Attributes\DataProvider('emptySecrets')]
    public function testEmptySecretIsRejected(mixed $secret): void
    {
        $forged = ['X-Wazzapo-Timestamp' => (string) time(), 'X-Wazzapo-Signature-V2' => 'v2=' . hash_hmac('sha256', time() . '.' . self::BODY, '')];
        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Секрет вебхука пуст');
        Webhooks::verify(self::BODY, $forged, $secret);
    }

    public function testSignWithEmptySecretFails(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhooks::sign(self::BODY, '');
    }

    public function testOneOfSeveralSignatures(): void
    {
        $good = Webhooks::sign(self::BODY, self::SECRET, 1_760_000_000);
        $old = Webhooks::sign(self::BODY, 'old_secret', 1_760_000_000)['X-Wazzapo-Signature-V2'];
        $good['X-Wazzapo-Signature-V2'] = $old . ', ' . $good['X-Wazzapo-Signature-V2'];
        self::assertSame('message.incoming', Webhooks::verify(self::BODY, $good, self::SECRET, 300, 1_760_000_000)['event']);
        $this->expectException(WebhookVerificationException::class);
        Webhooks::verify(self::BODY, $good, 'third', 300, 1_760_000_000);
    }

    public function testNotJsonWithValidSignature(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhooks::verify('not json', Webhooks::sign('not json', self::SECRET, 1_760_000_000), self::SECRET, 300, 1_760_000_000);
    }

    public function testDeliveryId(): void
    {
        self::assertSame('d-42', Webhooks::deliveryId(['x-wazzapo-delivery-id' => 'd-42']));
        self::assertNull(Webhooks::deliveryId([]));
        self::assertSame('X-Wazzapo-Delivery-Id', Webhooks::DELIVERY_ID_HEADER);
    }

    public function testEventsFromSpec(): void
    {
        self::assertContains('message.incoming', Webhooks::events());
        self::assertContains('deal.stage_changed', Webhooks::events());
    }
}
