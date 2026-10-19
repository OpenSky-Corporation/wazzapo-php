<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use PHPUnit\Framework\TestCase;
use Wazzapo\Exception\WazzapoException;
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
        $this->expectException(WazzapoException::class);
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

    public function testEventsFromSpec(): void
    {
        self::assertContains('message.incoming', Webhooks::events());
        self::assertContains('deal.stage_changed', Webhooks::events());
    }
}
