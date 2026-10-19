<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

use Wazzapo\Transport\Response;

/** Ответ с ошибкой → исключение нужного класса по статусу и error.code. */
final class ErrorFactory
{
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
        $class = self::classFor($response->status, $code);
        return new $class($message, $response->status, $code, $details, $requestId, $response->headers);
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
            $status === 401 => AuthenticationException::class,
            $status === 402 => PaymentRequiredException::class,
            $status === 403 => PermissionDeniedException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 422 => UnprocessableException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };
    }
}
