<?php

declare(strict_types=1);

namespace Wazzapo\Transport;

use Wazzapo\Exception\ConnectionException;
use Wazzapo\Exception\TimeoutException;

/** Отправка одного запроса. Своя реализация нужна для тестов или своего HTTP-клиента. */
interface Transport
{
    /**
     * @throws ConnectionException сеть недоступна, ответа нет
     * @throws TimeoutException сервер не ответил за timeout
     */
    public function send(Request $request): Response;
}
