<?php

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use Illuminate\Database\ConnectionInterface;

final class LaravelTransactionManager implements ITransactionManagerPort
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    public function run(callable $callback): mixed
    {
        return $this->connection->transaction($callback);
    }
}
