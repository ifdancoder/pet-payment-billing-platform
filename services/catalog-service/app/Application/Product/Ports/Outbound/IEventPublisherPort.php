<?php

namespace App\Application\Product\Ports\Outbound;

interface IEventPublisherPort
{
    public function publish(object $event): void;
}
