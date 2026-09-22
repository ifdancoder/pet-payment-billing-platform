<?php

namespace App\Application\Invoice\Commands\CreateInvoice;

use App\Application\Invoice\IntegrationEvents\InvoiceCreatedIntegrationEvent;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PriceId;
use App\Domain\Invoice\ValueObjects\ProductId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreateInvoiceHandler
{
    public function __construct(
        private readonly IInvoiceRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    /**
     * Builds and persists the Invoice for one billing cycle, or returns
     * null without doing anything when this exact event has already been
     * processed (an at-least-once redelivery of subscription.created.v1 /
     * a renewal event).
     */
    public function handle(CreateInvoiceCommand $command): ?Invoice
    {
        return $this->transaction->run(function () use ($command): ?Invoice {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $subscriptionId = SubscriptionId::fromString($command->subscriptionId);
            $periodStart = $command->periodStart;
            $periodEnd = $this->periodEnd($periodStart, $command->billingInterval, $command->billingIntervalCount);

            // A second, different event for the same subscription and
            // period must not double-bill either — this is the business
            // idempotency layer on top of the Inbox's event-id dedup.
            $existing = $this->repository->findByBillingCycle($subscriptionId, $periodStart);
            if ($existing !== null) {
                return $existing;
            }

            $currency = Currency::from($command->currency);
            $line = InvoiceLine::create(
                InvoiceLineId::generate(),
                $command->productId === null ? null : ProductId::fromString($command->productId),
                $command->priceId === null ? null : PriceId::fromString($command->priceId),
                $command->description,
                Money::of($command->amountMinorUnits, $currency),
                1,
            );

            $invoice = Invoice::create(
                InvoiceId::generate(),
                MerchantId::fromString($command->merchantId),
                CustomerId::fromString($command->customerId),
                $subscriptionId,
                BillingPeriod::of($periodStart, $periodEnd),
                [$line],
                $command->renewal,
            );

            try {
                $this->repository->save($invoice);
            } catch (UniqueConstraintViolationException) {
                // Lost a race with a concurrent delivery for the same
                // billing cycle — the winner's invoice is the truth.
                return $this->repository->findByBillingCycle($subscriptionId, $periodStart);
            }

            $this->recordIntegrationEvents($invoice);

            return $invoice;
        });
    }

    private function periodEnd(DateTimeImmutable $start, string $billingInterval, int $billingIntervalCount): DateTimeImmutable
    {
        return match ($billingInterval) {
            'day' => $start->modify("+{$billingIntervalCount} day"),
            'week' => $start->modify("+{$billingIntervalCount} week"),
            'month' => $start->modify("+{$billingIntervalCount} month"),
            'year' => $start->modify("+{$billingIntervalCount} year"),
            default => throw new \ValueError("\"{$billingInterval}\" is not a valid billing interval label."),
        };
    }

    private function recordIntegrationEvents(Invoice $invoice): void
    {
        foreach ($invoice->pullRecordedEvents() as $event) {
            if ($event instanceof InvoiceCreated) {
                $this->outbox->add(InvoiceCreatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
