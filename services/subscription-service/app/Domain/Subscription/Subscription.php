<?php

namespace App\Domain\Subscription;

use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\Events\SubscriptionCanceled;
use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Domain\Subscription\Events\SubscriptionMarkedPastDue;
use App\Domain\Subscription\Events\SubscriptionRenewalDue;
use App\Domain\Subscription\Exceptions\InvalidSubscriptionTransition;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

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
        private DateTimeImmutable $currentPeriodStart,
        private DateTimeImmutable $currentPeriodEnd,
        private bool $renewalPending,
    ) {}

    public static function create(
        SubscriptionId $id,
        MerchantId $merchantId,
        CustomerId $customerId,
        PriceSnapshot $priceSnapshot,
        ?DateTimeImmutable $periodStart = null,
    ): self {
        $periodStart ??= new DateTimeImmutable;
        $subscription = new self(
            $id,
            $merchantId,
            $customerId,
            $priceSnapshot,
            SubscriptionStatus::Pending,
            $periodStart,
            self::nextPeriodEnd($periodStart, $priceSnapshot),
            true,
        );
        $subscription->recordEvent(new SubscriptionCreated($id, $merchantId, $customerId, $priceSnapshot, $periodStart));

        return $subscription;
    }

    public static function reconstitute(
        SubscriptionId $id,
        MerchantId $merchantId,
        CustomerId $customerId,
        PriceSnapshot $priceSnapshot,
        SubscriptionStatus $status,
        ?DateTimeImmutable $currentPeriodStart = null,
        ?DateTimeImmutable $currentPeriodEnd = null,
        ?bool $renewalPending = null,
    ): self {
        $currentPeriodStart ??= new DateTimeImmutable;

        return new self(
            $id,
            $merchantId,
            $customerId,
            $priceSnapshot,
            $status,
            $currentPeriodStart,
            $currentPeriodEnd ?? self::nextPeriodEnd($currentPeriodStart, $priceSnapshot),
            $renewalPending ?? $status === SubscriptionStatus::Pending,
        );
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

    public function currentPeriodStart(): DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function currentPeriodEnd(): DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function renewalPending(): bool
    {
        return $this->renewalPending;
    }

    /** Releases the pending-cycle guard after initial or renewal payment. */
    public function invoicePaid(): void
    {
        $this->activate();
        $this->renewalPending = false;
    }

    /** Initial failure stays Pending; renewal failure moves Active to PastDue. */
    public function invoicePaymentFailed(): void
    {
        if ($this->status === SubscriptionStatus::Active) {
            $this->markPastDue();
        }

        $this->renewalPending = false;
    }

    /** The pending guard prevents overlapping renewal cycles. */
    public function renew(DateTimeImmutable $asOf): void
    {
        if ($this->status !== SubscriptionStatus::Active
            || $this->renewalPending
            || $this->currentPeriodEnd > $asOf) {
            return;
        }

        $periodStart = $this->currentPeriodEnd;
        $periodEnd = self::nextPeriodEnd($periodStart, $this->priceSnapshot);

        $this->currentPeriodStart = $periodStart;
        $this->currentPeriodEnd = $periodEnd;
        $this->renewalPending = true;
        $this->recordEvent(new SubscriptionRenewalDue(
            $this->id,
            $this->merchantId,
            $this->customerId,
            $this->priceSnapshot,
            $periodStart,
        ));
    }

    /** Renewal payments may activate an already-active subscription. */
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

    /** Repeated renewal failures are idempotent. */
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

    private static function nextPeriodEnd(DateTimeImmutable $start, PriceSnapshot $snapshot): DateTimeImmutable
    {
        $period = $snapshot->billingPeriod();
        $count = $period->count();

        return $start->modify("+{$count} {$period->interval()->label()}");
    }
}
