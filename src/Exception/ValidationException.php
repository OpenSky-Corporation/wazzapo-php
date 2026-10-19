<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 422 VALIDATION_ERROR: тело или параметры не прошли проверку, список полей в fields(). */
class ValidationException extends UnprocessableEntityException
{
    /**
     * Поля, не прошедшие проверку: [['field' => 'body.to', 'message' => 'Field required'], …].
     *
     * @return list<array{field: string, message: string}>
     */
    public function fields(): array
    {
        $out = [];
        foreach ($this->details ?? [] as $item) {
            if (is_array($item) && isset($item['field'], $item['message'])) {
                $out[] = ['field' => (string) $item['field'], 'message' => (string) $item['message']];
            }
        }
        return $out;
    }
}
