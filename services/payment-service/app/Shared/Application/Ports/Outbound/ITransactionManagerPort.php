<?php

namespace App\Shared\Application\Ports\Outbound;

interface ITransactionManagerPort
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
