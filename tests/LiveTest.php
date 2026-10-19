<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use PHPUnit\Framework\TestCase;
use Wazzapo\Wazzapo;

/** Сквозная проверка на тестовом ключе wz_test_: ничего не отправляет в WhatsApp. */
final class LiveTest extends TestCase
{
    private Wazzapo $client;

    protected function setUp(): void
    {
        $key = getenv('WAZZAPO_TEST_API_KEY') ?: '';
        if (!str_starts_with($key, 'wz_test_')) {
            self::markTestSkipped('нет WAZZAPO_TEST_API_KEY с ключом wz_test_');
        }
        $this->client = new Wazzapo(['api_key' => $key]);
    }

    public function testListNumbersAndSendText(): void
    {
        $numbers = $this->client->phoneNumbers->list()['data'];
        self::assertNotEmpty($numbers, 'в тестовом аккаунте нет номеров');
        $out = $this->client->messages->send([
            'phone_number_id' => $numbers[0]['phone_number_id'],
            'to' => '77074802540',
            'type' => 'text',
            'text' => ['body' => 'Проверка SDK PHP'],
        ]);
        self::assertStringStartsWith('wamid.SANDBOX', $out['message_id']);
    }

    public function testCreateContact(): void
    {
        // Номер случайный: тестовый ключ сверяет его с настоящими контактами и ничего не записывает
        $phone = '7747' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        $contact = $this->client->contacts->create(['phone' => $phone, 'name' => 'Проверка SDK']);
        self::assertStringStartsWith('sandbox_', $contact['id']);
    }
}
