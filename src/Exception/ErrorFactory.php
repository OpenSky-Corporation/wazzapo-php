<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

use Wazzapo\Transport\Response;

/** Ответ с ошибкой → исключение нужного класса по статусу и error.code. */
final class ErrorFactory
{
    /** Код по статусу, если ответ не в формате API (страница прокси): та же таблица, что на сервере. */
    private const STATUS_CODE = [
        400 => 'BAD_REQUEST',
        401 => 'UNAUTHORIZED',
        403 => 'FORBIDDEN',
        404 => 'NOT_FOUND',
        405 => 'METHOD_NOT_ALLOWED',
        409 => 'CONFLICT',
        413 => 'PAYLOAD_TOO_LARGE',
        415 => 'UNSUPPORTED_MEDIA_TYPE',
        422 => 'VALIDATION_ERROR',
        429 => 'RATE_LIMITED',
        500 => 'INTERNAL_ERROR',
        501 => 'NOT_IMPLEMENTED',
        502 => 'BAD_GATEWAY',
        503 => 'SERVICE_UNAVAILABLE',
        504 => 'GATEWAY_TIMEOUT',
    ];

    public static function fromResponse(Response $response): ApiException
    {
        $data = json_decode($response->body, true);
        $error = is_array($data) && is_array($data['error'] ?? null) ? $data['error'] : [];
        $code = isset($error['code']) && is_string($error['code']) ? $error['code'] : null;
        $message = isset($error['message']) && is_string($error['message'])
            ? $error['message']
            : 'Ошибка API Wazzapo, HTTP ' . $response->status;
        $details = isset($error['details']) && is_array($error['details']) ? $error['details'] : null;
        $requestId = is_array($data) && is_string($data['request_id'] ?? null)
            ? $data['request_id']
            : $response->header('x-request-id');
        // Класс по настоящему коду; без кода только по статусу, а errorCode из таблицы, чтобы не был пустым
        $class = self::classFor($response->status, $code);
        $errorCode = $code ?? self::STATUS_CODE[$response->status] ?? 'HTTP_' . $response->status;
        return new $class($message, $response->status, $errorCode, $details, $requestId, $response->headers);
    }

    /** @return class-string<ApiException> */
    private static function classFor(int $status, ?string $code): string
    {
        if ($code === 'USAGE_LIMIT_EXCEEDED') {
            return UsageLimitException::class;
        }
        if ($code === 'outside_24h_window') {
            return OutsideWindowException::class;
        }
        if ($code !== null && str_starts_with($code, 'template_')) {
            return TemplateException::class;
        }
        if ($code === 'VALIDATION_ERROR') {
            return ValidationException::class;
        }
        return match (true) {
            $status === 400 => BadRequestException::class,
            $status === 401 => AuthenticationException::class,
            $status === 402 => PaymentRequiredException::class,
            $status === 403 => PermissionDeniedException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 422 => UnprocessableEntityException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };
    }
}
