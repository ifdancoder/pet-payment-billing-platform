<?php

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use Illuminate\Database\DatabaseManager;

final class LaravelTransactionManager implements ITransactionManagerPort
{
    public function __construct(private readonly DatabaseManager $database) {}

    public function run(callable $callback): mixed
    {
        return $this->database->transaction($callback);
    }
}
