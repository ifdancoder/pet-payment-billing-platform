<?php

namespace App\Presentation\Http\V1\Resources;

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
