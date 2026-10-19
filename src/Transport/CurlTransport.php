<?php

declare(strict_types=1);

namespace Wazzapo\Transport;

use Wazzapo\Exception\ConnectionException;
use Wazzapo\Exception\TimeoutException;

/** Транспорт на ext-curl, без внешних зависимостей. */
final class CurlTransport implements Transport
{
    public function send(Request $request): Response
    {
        $headers = [];
        $ch = curl_init($request->url);
        $lines = [];
        foreach ($request->headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => (int) ($request->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) (min($request->timeout, 10.0) * 1000),
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);
        if ($request->body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }
        $body = curl_exec($ch);
        if ($body === false) {
            $errno = curl_errno($ch);
            $message = curl_error($ch);
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                throw new TimeoutException('Сервер Wazzapo не ответил вовремя: ' . $message);
            }
            throw new ConnectionException('Нет связи с сервером Wazzapo: ' . $message);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        return new Response($status, $headers, (string) $body);
    }
}
