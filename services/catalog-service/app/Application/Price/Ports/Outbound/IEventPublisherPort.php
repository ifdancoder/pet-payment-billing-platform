<?php

namespace App\Application\Price\Ports\Outbound;

interface IEventPublisherPort
{
    public function publish(object $event): void;
}
