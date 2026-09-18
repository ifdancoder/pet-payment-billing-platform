<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionCommand;
use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueCommand;
use App\Application\Subscription\Ports\Inbound\ISubscriptionServicePort;
use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionQuery;
use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsQuery;
use App\Presentation\Http\V1\Requests\CreateSubscriptionRequest;
use App\Presentation\Http\V1\Resources\SubscriptionResource;
use App\Presentation\Http\V1\Resources\SubscriptionResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class SubscriptionController extends Controller
{
    public function __construct(private readonly ISubscriptionServicePort $subscriptionService) {}

    public function index(string $merchant): JsonResponse
    {
        $subscriptions = $this->subscriptionService->listSubscriptions(new ListSubscriptionsQuery($merchant));

        return (new SubscriptionResourceCollection($subscriptions))->response();
    }

    public function store(CreateSubscriptionRequest $request, string $merchant): JsonResponse
    {
        $subscription = $this->subscriptionService->createSubscription(new CreateSubscriptionCommand(
            $merchant,
            $request->validated('customer_id'),
            $request->validated('price_id'),
        ));

        return SubscriptionResource::make($subscription)->response()->setStatusCode(201);
    }

    public function show(string $merchant, string $subscription): JsonResponse
    {
        $found = $this->subscriptionService->getSubscription(new GetSubscriptionQuery($merchant, $subscription));

        return SubscriptionResource::make($found)->response();
    }

    public function activate(string $merchant, string $subscription): JsonResponse
    {
        $activated = $this->subscriptionService->activateSubscription(new ActivateSubscriptionCommand($merchant, $subscription));

        return SubscriptionResource::make($activated)->response();
    }

    public function markPastDue(string $merchant, string $subscription): JsonResponse
    {
        $marked = $this->subscriptionService->markSubscriptionPastDue(new MarkSubscriptionPastDueCommand($merchant, $subscription));

        return SubscriptionResource::make($marked)->response();
    }

    public function cancel(string $merchant, string $subscription): JsonResponse
    {
        $canceled = $this->subscriptionService->cancelSubscription(new CancelSubscriptionCommand($merchant, $subscription));

        return SubscriptionResource::make($canceled)->response();
    }
}
