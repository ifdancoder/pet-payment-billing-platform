<?php

namespace App\Presentation\Subscription\Adapters\Inbound\Http\Controllers;

use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Ports\Inbound\ISubscriptionServicePort;
use App\Presentation\Subscription\Adapters\Inbound\Http\Requests\CreateSubscriptionRequest;
use App\Presentation\Subscription\Adapters\Inbound\Http\Resources\SubscriptionResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class SubscriptionController extends Controller
{
    public function __construct(private readonly ISubscriptionServicePort $subscriptionService) {}

    public function store(CreateSubscriptionRequest $request, string $merchant): JsonResponse
    {
        $subscription = $this->subscriptionService->createSubscription(new CreateSubscriptionCommand(
            $merchant,
            $request->validated('customer_id'),
            $request->validated('price_id'),
        ));

        return SubscriptionResource::make($subscription)->response()->setStatusCode(201);
    }
}
