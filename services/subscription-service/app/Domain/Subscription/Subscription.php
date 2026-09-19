<?php

namespace App\Domain\Subscription;

use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\Events\SubscriptionCanceled;
use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Domain\Subscription\Events\SubscriptionMarkedPastDue;
use App\Domain\Subscription\Exceptions\InvalidSubscriptionTransition;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Shared\Domain\ValueObjects\MerchantId;

final class Subscription
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly SubscriptionId $id,
        private readonly MerchantId $merchantId,
        private readonly CustomerId $customerId,
        private readonly PriceSnapshot $priceSnapshot,
        private SubscriptionStatus $status,
    ) {}

    public static function create(SubscriptionId $id, MerchantId $merchantId, CustomerId $customerId, PriceSnapshot $priceSnapshot): self
    {
        $subscription = new self($id, $merchantId, $customerId, $priceSnapshot, SubscriptionStatus::Pending);
        $subscription->recordEvent(new SubscriptionCreated($id, $merchantId, $customerId, $priceSnapshot));

        return $subscription;
    }

    /**
     * Rebuilds a Subscription from already-persisted data. Unlike create(),
     * this does not record a SubscriptionCreated event.
     */
    public static function reconstitute(
        SubscriptionId $id,
        MerchantId $merchantId,
        CustomerId $customerId,
        PriceSnapshot $priceSnapshot,
        SubscriptionStatus $status,
    ): self {
        return new self($id, $merchantId, $customerId, $priceSnapshot, $status);
    }

    public function id(): SubscriptionId
    {
        return $this->id;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function priceSnapshot(): PriceSnapshot
    {
        return $this->priceSnapshot;
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    /**
     * Activates a Pending subscription on first successful payment, or
     * reactivates one that had fallen PastDue after a later successful
     * payment.
     *
     * Activating an already-Active subscription is a silent no-op rather
     * than an error: invoice.paid.v1 fires on every renewal cycle, not
     * just the first one, so by the time a later renewal is paid the
     * subscription is normally Active already — that's the expected
     * case, not a redelivery bug, and it must not fail either way.
     */
    public function activate(): void
    {
        if ($this->status === SubscriptionStatus::Active) {
            return;
        }

        if (! in_array($this->status, [SubscriptionStatus::Pending, SubscriptionStatus::PastDue], true)) {
            throw InvalidSubscriptionTransition::forAction($this->id, 'be activated', $this->status);
        }

        $this->status = SubscriptionStatus::Active;
        $this->recordEvent(new SubscriptionActivated($this->id));
    }

    /**
     * Marking an already-PastDue subscription past due again is a silent
     * no-op rather than an error: a second, later payment attempt can
     * fail too (invoice.payment_failed.v1 firing more than once for the
     * same subscription across retries), and that must not fail either.
     */
    public function markPastDue(): void
    {
        if ($this->status === SubscriptionStatus::PastDue) {
            return;
        }

        if ($this->status !== SubscriptionStatus::Active) {
            throw InvalidSubscriptionTransition::forAction($this->id, 'be marked past due', $this->status);
        }

        $this->status = SubscriptionStatus::PastDue;
        $this->recordEvent(new SubscriptionMarkedPastDue($this->id));
    }

    /**
     * Cancels the subscription immediately. cancel_at_period_end is a
     * later slice.
     */
    public function cancel(): void
    {
        if (! in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::PastDue], true)) {
            throw InvalidSubscriptionTransition::forAction($this->id, 'be canceled', $this->status);
        }

        $this->status = SubscriptionStatus::Canceled;
        $this->recordEvent(new SubscriptionCanceled($this->id));
    }

    /**
     * @return array<object>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
