<?php

namespace App\Application\Subscription\Ports\Inbound;

use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Domain\Subscription\Subscription;

interface ISubscriptionServicePort
{
    public function createSubscription(CreateSubscriptionCommand $command): Subscription;
}
