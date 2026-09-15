<?php

namespace App\Application\Customer\Ports\Outbound;

interface IEventPublisherPort
{
    public function publish(object $event): void;
}
