<?php

namespace App\Shared\Application\Ports\Outbound;

use App\Shared\Application\ReadModels\OutboxMessage;

interface IEventPublisherPort
{
    public function publish(OutboxMessage $message): void;
}
