<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы media: media.download, media.upload. */
final class Media
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Скачать файл
     *
     * Скачивает файл по media_id: тело ответа сам файл, тип в заголовке Content-Type.
     *
     * Так забирают фото, голосовые и документы, которые прислал клиент. Сохраните файл у себя:
     * Meta хранит его ограниченное время. Ошибки: 404 phone_number_not_found или NOT_FOUND (номер не
     * найден; файла с таким id нет в Meta: удалён, срок хранения вышел или id неверный; id sandbox_…
     * от тестового ключа, такие файлы не хранятся), 400 BAD_REQUEST (номер не через Cloud API),
     * 502 provider_error (сбой Meta, повторите позже).
     *
     * Параметры запроса:
     * - phone_number_id (string, обязательно): id номера: phone_number_id из GET /api/v2/external/phone-numbers,
     *   например 123456789012345
     *
     * @param string $mediaId id файла: media.id из входящего сообщения (вебхук message.incoming) или из POST /media
     * @param string $phoneNumberId id номера: phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return string
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function download(string $mediaId, string $phoneNumberId, array $params = []): string
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/media/' . rawurlencode($mediaId), ['phone_number_id' => $phoneNumberId] + $query, null, $options, 'binary');
    }

    /**
     * Загрузить файл
     *
     * Загружает файл в WhatsApp и возвращает media_id для отправки в POST /messages/send.
     *
     * Нужен, когда у файла нет публичной ссылки, и для голосовых OGG/OPUS (по ссылке Meta их
     * не доставляет). Пример: curl -F "file=@schet_1024.pdf" "…/media?phone_number_id=123456789012345".
     * media_id действует 30 дней. Повтор с тем же Idempotency-Key
     * не загружает файл второй раз. Ошибки: 400 BAD_REQUEST (номер не через Cloud API),
     * 413 PAYLOAD_TOO_LARGE, 422 media_unavailable (формат не подходит).
     *
     * Поля тела запроса:
     * - file (string, обязательно): Файл формой multipart/form-data, поле file, до 100 МБ: картинка, видео, аудио,
     *   документ или стикер
     *
     * Параметры запроса:
     * - phone_number_id (string, обязательно): id номера: phone_number_id из GET /api/v2/external/phone-numbers,
     *   например 123456789012345
     *
     * @param string $phoneNumberId id номера: phone_number_id из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param string|\CURLFile $file путь к файлу или CURLFile
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{media_id: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function upload(string $phoneNumberId, string|\CURLFile $file, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/media', ['phone_number_id' => $phoneNumberId] + $query, ['file' => $file], $options, 'multipart');
    }
}
