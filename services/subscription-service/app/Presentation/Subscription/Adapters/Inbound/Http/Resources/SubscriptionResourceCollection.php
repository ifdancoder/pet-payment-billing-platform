<?php

namespace App\Presentation\Subscription\Adapters\Inbound\Http\Resources;

use App\Domain\Subscription\Subscription;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class SubscriptionResourceCollection extends ResourceCollection
{
    public $collects = SubscriptionResource::class;

    /**
     * @param  array<int, Subscription>  $subscriptions
     */
    public function __construct(array $subscriptions)
    {
        parent::__construct($subscriptions);
    }
}
