<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/**
 * Любая ошибка SDK. Ориентируйтесь на $errorCode (машинный код из описания API),
 * $requestId сообщите в поддержку.
 */
class WazzapoException extends \RuntimeException
{
    /**
     * @param array<string, mixed>|list<array{field: string, message: string}>|null $details
     * @param array<string, string> $headers
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        public readonly array|null $details = null,
        public readonly ?string $requestId = null,
        public readonly array $headers = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
