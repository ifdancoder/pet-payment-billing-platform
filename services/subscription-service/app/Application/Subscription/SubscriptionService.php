<?php

namespace App\Application\Subscription;

use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionHandler;
use App\Application\Subscription\Ports\Inbound\ISubscriptionServicePort;
use App\Domain\Subscription\Subscription;

final class SubscriptionService implements ISubscriptionServicePort
{
    public function __construct(
        private readonly CreateSubscriptionHandler $createSubscriptionHandler,
    ) {}

    public function createSubscription(CreateSubscriptionCommand $command): Subscription
    {
        return $this->createSubscriptionHandler->handle($command);
    }
}
