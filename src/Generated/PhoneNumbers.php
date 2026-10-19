<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы phone_numbers: phone_numbers.create, phone_numbers.delete, phone_numbers.get, phone_numbers.get_business_profile, phone_numbers.list, phone_numbers.update_business_profile. */
final class PhoneNumbers
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Подключить номер
     *
     * Начинает подключение нового номера WhatsApp.
     *
     * В ответе может быть embedded_signup_url: откройте его во всплывающем окне, владелец
     * номера войдёт в Facebook и подтвердит номер в Meta. Пока подключение не завершено,
     * status=pending. Номер расходует лимит устройств тарифа.
     * Ошибки: 402 DEVICE_QUOTA_EXCEEDED (лимит номеров), 503 SERVICE_UNAVAILABLE
     * (подключение через API недоступно, подключите номер в кабинете).
     *
     * Поля тела запроса:
     * - display_name (string, обязательно): Название подключения в кабинете, видно только вам, например «Отдел
     *   продаж»
     * - phone_e164 (string|null): Номер, который подключаете, в международном формате, например +77071234567
     *
     * @param array{display_name: string, phone_e164?: string|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{channel?: string, code_verification_status?: 'VERIFIED'|'NOT_VERIFIED'|'EXPIRED'|null, display_phone_number?: string|null, embedded_signup_url?: string|null, phone_number_id: string, provider_type: string, quality_rating?: 'GREEN'|'YELLOW'|'RED'|'UNKNOWN'|null, status: 'pending'|'connecting'|'connected'|'disconnected'|'failed', verified_name?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/phone-numbers', $query, $body, $options, 'json');
    }

    /**
     * Отключить номер
     *
     * Отключает номер от Wazzapo: сообщения с него больше не отправляются и не приходят.
     *
     * Переписка в кабинете сохраняется. Тестовый ключ номер не отключает.
     * Ошибка: 404 NOT_FOUND, если номера нет в этом аккаунте.
     *
     * @param string $phoneNumberId id номера: поле phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return null
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function delete(string $phoneNumberId, array $params = []): ?array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('DELETE', '/phone-numbers/' . rawurlencode($phoneNumberId), $query, null, $options, 'json');
    }

    /**
     * Номер
     *
     * Возвращает один номер: статус подключения, имя компании, оценку качества Meta.
     *
     * Проверяйте status=connected перед рассылкой. Ошибка: 404 NOT_FOUND, если номера
     * нет в этом аккаунте.
     *
     * @param string $phoneNumberId id номера: поле phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{channel?: string, code_verification_status?: 'VERIFIED'|'NOT_VERIFIED'|'EXPIRED'|null, display_phone_number?: string|null, phone_number_id: string, provider_type: string, quality_rating?: 'GREEN'|'YELLOW'|'RED'|'UNKNOWN'|null, status: 'pending'|'connecting'|'connected'|'disconnected'|'failed', verified_name?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $phoneNumberId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/phone-numbers/' . rawurlencode($phoneNumberId), $query, null, $options, 'json');
    }

    /**
     * Профиль компании
     *
     * Возвращает профиль компании в WhatsApp: описание, адрес, почту, сайты, фото.
     *
     * Клиенты видят его, открыв карточку номера. Только для номеров WhatsApp Cloud API.
     * Ошибки: 404 NOT_FOUND, 501 not_supported.
     *
     * @param string $phoneNumberId id номера: поле phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{about?: string|null, address?: string|null, description?: string|null, email?: string|null, profile_picture_url?: string|null, vertical?: string|null, websites?: list<string>|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function getBusinessProfile(string $phoneNumberId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/phone-numbers/' . rawurlencode($phoneNumberId) . '/business-profile', $query, null, $options, 'json');
    }

    /**
     * Список номеров
     *
     * Возвращает номера WhatsApp и другие каналы, подключённые к аккаунту ключа.
     *
     * С этого метода начинают: phone_number_id из ответа нужен для отправки сообщений,
     * загрузки файлов и шаблонов. Номера WhatsApp идут первыми, за ними остальные каналы
     * (поле channel); только WhatsApp: ?channel=whatsapp. Номер подключают в кабинете Wazzapo
     * или методом POST /phone-numbers. Отключённые номера в списке не показываются.
     *
     * Параметры запроса:
     * - channel ('whatsapp'|'telegram'|'instagram'|'olx'|'satu'|'email'|'webchat'|null): Только этот канал:
     *   whatsapp, telegram, instagram, olx, satu, email, webchat. Для отправки сообщений и шаблонов нужен whatsapp
     *
     * @param array{channel?: 'whatsapp'|'telegram'|'instagram'|'olx'|'satu'|'email'|'webchat'|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, meta?: array<string, mixed>}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/phone-numbers', $query, null, $options, 'json');
    }

    /**
     * Изменить профиль компании
     *
     * Меняет профиль компании в WhatsApp. Передайте только поля, которые меняете.
     *
     * Фото профиля этим методом не меняется. Тестовый ключ ничего не меняет.
     * Ошибки: 404 NOT_FOUND, 422 invalid_parameters (Meta не приняла значение, например
     * неизвестная отрасль vertical), 501 not_supported.
     *
     * Поля тела запроса:
     * - about (string|null): Статус под именем в WhatsApp, до 139 знаков
     * - address (string|null): Адрес, до 256 знаков, например «Астана, пр. Абая, 15»
     * - description (string|null): Описание компании, до 512 знаков
     * - email (string|null): Почта для клиентов, до 128 знаков
     * - vertical (string|null): Отрасль из списка Meta: BEAUTY, RETAIL, EDU, HEALTH, RESTAURANT, PROF_SERVICES,
     *   OTHER и другие
     * - websites (list<string>|null): Сайты компании, до 2 адресов с http:// или https://
     *
     * @param string $phoneNumberId id номера: поле phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{about?: string|null, address?: string|null, description?: string|null, email?: string|null, vertical?: string|null, websites?: list<string>|null} $body
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{success?: bool}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function updateBusinessProfile(string $phoneNumberId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('PATCH', '/phone-numbers/' . rawurlencode($phoneNumberId) . '/business-profile', $query, $body, $options, 'json');
    }
}
