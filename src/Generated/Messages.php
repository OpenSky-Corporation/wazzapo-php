<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы messages: messages.mark_read, messages.react, messages.send. */
final class Messages
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Отметить прочитанными
     *
     * Отмечает входящие сообщения прочитанными: клиент увидит синие галочки.
     *
     * Передайте wamid входящих из вебхука message.incoming. Отметка в WhatsApp не меняет
     * счётчик непрочитанных в кабинете. Тестовый ключ ничего не отправляет.
     * Ошибки: 404 phone_number_not_found, 501 not_supported (номер не через Cloud API).
     *
     * Поля тела запроса:
     * - message_ids (list<string>, обязательно): wamid входящих сообщений, которые отметить прочитанными. wamid
     *   сообщения: из вебхука message.incoming или из списка сообщений переписки, например
     *   wamid.HBgLNzcwNzEyMzQ1Njc...
     * - phone_number_id (string, обязательно): id номера WhatsApp, с которого работаете: поле phone_number_id из GET
     *   /api/v2/external/phone-numbers, например 123456789012345
     *
     * @param array{message_ids: list<string>, phone_number_id: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{success: bool}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function markRead(array $body, array $options = []): array
    {
        return $this->http->request('POST', '/messages/mark-read', [], $body, $options, 'json');
    }

    /**
     * Поставить реакцию
     *
     * Ставит эмодзи-реакцию на сообщение клиента или своё; пустой emoji снимает реакцию.
     *
     * Meta принимает реакцию на сообщения не старше 30 дней. Тестовый ключ ничего не отправляет.
     * Ошибки: 404 phone_number_not_found, 422 invalid_parameters (неверный message_id), 501 not_supported.
     *
     * Поля тела запроса:
     * - emoji (string, обязательно): Один эмодзи, например 👍. Пустая строка снимает реакцию
     * - message_id (string, обязательно): На какое сообщение реакция. wamid сообщения: из вебхука message.incoming
     *   или из списка сообщений переписки, например wamid.HBgLNzcwNzEyMzQ1Njc...
     * - phone_number_id (string, обязательно): id номера WhatsApp, с которого работаете: поле phone_number_id из GET
     *   /api/v2/external/phone-numbers, например 123456789012345
     * - to (string, обязательно): Номер клиента, которому принадлежит сообщение: цифры с кодом страны, плюс в начале
     *   можно, например 77071234567. Уходит в Meta как есть: без пробелов, 8 в начале на 7 не заменяется
     *
     * @param array{emoji: string, message_id: string, phone_number_id: string, to: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{message_id?: string|null, status: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function react(array $body, array $options = []): array
    {
        return $this->http->request('POST', '/messages/reaction', [], $body, $options, 'json');
    }

    /**
     * Отправить сообщение
     *
     * Отправляет клиенту сообщение с номера phone_number_id (GET /api/v2/external/phone-numbers).
     *
     * **Окно 24 часа.** Обычное сообщение (текст, файл, кнопки, место) можно отправить, только если клиент писал вам
     * за последние 24 часа. Первое сообщение и всё вне окна: только одобренный шаблон (type=template, имена в GET
     * /api/v2/external/templates).
     *
     * **Файлы.** Передайте link (публичная ссылка https) или id: сначала загрузите файл в POST
     * /api/v2/external/media и возьмите media_id из ответа.
     *
     * **Повтор.** Заголовок Idempotency-Key: повтор с тем же ключом не отправит второе сообщение, а вернёт первый
     * ответ.
     *
     * **Тестовый ключ** wz_test_… ничего не отправляет и отвечает id вида wamid.SANDBOX….
     *
     * **После отправки** сообщение появляется в переписке кабинета, а на ваш адрес приходит вебхук message.outgoing;
     * статусы доставки и прочтения приходят вебхуком message.status с тем же message_id.
     *
     * **Лимиты.** Каждое сообщение расходует лимит тарифа (403 USAGE_LIMIT_EXCEEDED); частоту запросов показывают
     * заголовки X-RateLimit-*. Meta тоже ограничивает частоту (429 rate_limited).
     *
     * **Как узнать, открыто ли окно.** Окно 24 часа отсчитывается от последнего входящего сообщения клиента:
     * возьмите timestamp последнего сообщения с direction=in из GET
     * /api/v2/external/conversations/{conversation_id}/messages (или время вебхука message.incoming) и прибавьте 24
     * часа. Позже этого времени отправляйте шаблон.
     *
     * **Сумма в шаблоне** (type=currency): amount_1000 = тенге × 1000, 150 000 ₸ = 150000000. В сделках сумма в
     * тиынах (× 100): 150 000 ₸ = 15000000.
     *
     * **Частые ошибки:** 422 outside_24h_window (окно закрыто, отправьте шаблон), 422 template_not_found (нет
     * одобренного шаблона с таким именем и языком; именно 422, а не 404: адрес /messages/send существует, не
     * подходит значение в теле. GET /templates/{template_id} с неизвестным id отвечает 404), 422 invalid_parameters
     * (type не совпадает с блоком, например type=text вместе с template, или файл без id и link), 422
     * recipient_unavailable (у номера нет WhatsApp), 422 media_unavailable (Meta не скачала файл по ссылке), 404
     * phone_number_not_found.
     *
     * Поля тела запроса:
     * - audio (array<string, mixed>|null): Для type=audio: MP3, M4A, AAC или OGG до 16 МБ; голосовое OGG/OPUS только
     *   по id
     * - contacts (list<array<string, mixed>>|null): Для type=contacts: одна или несколько карточек
     * - context (array<string, mixed>|null): Ответ на сообщение клиента: цитата над пузырём. С шаблоном не
     *   передаётся
     * - document (array<string, mixed>|null): Для type=document: PDF, Word, Excel и другие до 100 МБ
     * - image (array<string, mixed>|null): Для type=image: JPG или PNG до 5 МБ
     * - interactive (array<string, mixed>|null): Для type=interactive: кнопки, список, кнопка-ссылка или запрос
     *   местоположения
     * - location (array<string, mixed>|null): Для type=location: точка на карте
     * - phone_number_id (string, обязательно): id номера WhatsApp, с которого работаете: поле phone_number_id из GET
     *   /api/v2/external/phone-numbers, например 123456789012345
     * - sticker (array<string, mixed>|null): Для type=sticker: WEBP 512×512, до 100 КБ (анимированный до 500 КБ)
     * - template (array<string, mixed>|null): Для type=template: одобренный шаблон; единственный вид, который можно
     *   отправить вне окна 24 часа
     * - text (array<string, mixed>|null): Для type=text
     * - to (string, обязательно): Номер клиента: цифры с кодом страны, плюс в начале можно: 77071234567 или
     *   +77071234567. Пробелы, скобки и дефисы не принимаются (422 VALIDATION_ERROR). 8 в начале на 7 НЕ заменяется:
     *   87071234567 уйдёт в Meta как номер с кодом страны 8 и не дойдёт, пишите 77071234567
     * - type ('text'|'image'|'video'|'audio'|'document'|'template'|'interactive'|'location'|'contacts'|'sticker',
     *   обязательно): Вид сообщения; рядом нужен блок с тем же именем, например type=text и text={...}
     * - video (array<string, mixed>|null): Для type=video: MP4 или 3GP до 16 МБ
     *
     * @param array{audio?: array<string, mixed>|null, contacts?: list<array<string, mixed>>|null, context?: array<string, mixed>|null, document?: array<string, mixed>|null, image?: array<string, mixed>|null, interactive?: array<string, mixed>|null, location?: array<string, mixed>|null, phone_number_id: string, sticker?: array<string, mixed>|null, template?: array<string, mixed>|null, text?: array<string, mixed>|null, to: string, type: 'text'|'image'|'video'|'audio'|'document'|'template'|'interactive'|'location'|'contacts'|'sticker', video?: array<string, mixed>|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{message_id: string, provider_message_id?: string|null, status?: 'accepted'|'sent'|'delivered'|'read'|'failed'}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function send(array $body, array $options = []): array
    {
        return $this->http->request('POST', '/messages/send', [], $body, $options, 'json');
    }
}
