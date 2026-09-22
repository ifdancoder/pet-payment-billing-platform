<?php

namespace App\Application\Subscription\Commands\RenewSubscriptions;

use App\Application\Subscription\IntegrationEvents\SubscriptionRenewalDueIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionRenewalDue;
use App\Domain\Subscription\Subscription;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use DateTimeImmutable;

final class RenewSubscriptionsHandler
{
    public function __construct(
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(DateTimeImmutable $asOf, int $limit = 100): int
    {
        return $this->transaction->run(function () use ($asOf, $limit): int {
            $subscriptions = $this->repository->dueForRenewal($asOf, $limit);

            foreach ($subscriptions as $subscription) {
                $subscription->renew($asOf);
                $this->repository->save($subscription);
                $this->recordIntegrationEvents($subscription);
            }

            return count($subscriptions);
        });
    }

    private function recordIntegrationEvents(Subscription $subscription): void
    {
        foreach ($subscription->pullRecordedEvents() as $event) {
            if ($event instanceof SubscriptionRenewalDue) {
                $this->outbox->add(SubscriptionRenewalDueIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
