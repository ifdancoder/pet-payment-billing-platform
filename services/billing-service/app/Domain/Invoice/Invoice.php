<?php

namespace App\Domain\Invoice;

use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\Events\InvoicePaid;
use App\Domain\Invoice\Events\InvoiceVoided;
use App\Domain\Invoice\Exceptions\InvalidInvoice;
use App\Domain\Invoice\Exceptions\InvalidInvoiceTransition;
use App\Domain\Invoice\Exceptions\InvoiceAlreadyVoided;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class Invoice
{
    /** @var array<object> */
    private array $recordedEvents = [];

    /**
     * @param  InvoiceLine[]  $lines
     */
    private function __construct(
        private readonly InvoiceId $id,
        private readonly MerchantId $merchantId,
        private readonly CustomerId $customerId,
        private readonly SubscriptionId $subscriptionId,
        private readonly BillingPeriod $period,
        private readonly array $lines,
        private readonly Money $subtotal,
        private readonly Money $total,
        private InvoiceStatus $status,
        private ?PaymentId $paymentId = null,
        private ?DateTimeImmutable $paidAt = null,
        private ?DateTimeImmutable $voidedAt = null,
    ) {}

    /**
     * Builds a complete, immediately-payable Invoice from its lines. There
     * is no Draft status: an Invoice is only ever created once every line
     * is already known (from a subscription.created.v1 / renewal event),
     * so it starts out Open.
     *
     * @param  InvoiceLine[]  $lines
     */
    public static function create(
        InvoiceId $id,
        MerchantId $merchantId,
        CustomerId $customerId,
        SubscriptionId $subscriptionId,
        BillingPeriod $period,
        array $lines,
    ): self {
        if ($lines === []) {
            throw InvalidInvoice::mustHaveAtLeastOneLine();
        }

        $subtotal = array_reduce(
            $lines,
            fn (Money $carry, InvoiceLine $line): Money => $carry->add($line->total()),
            Money::of(0, $lines[0]->total()->currency()),
        );
        $total = $subtotal;

        $invoice = new self($id, $merchantId, $customerId, $subscriptionId, $period, $lines, $subtotal, $total, InvoiceStatus::Open);
        $invoice->recordEvent(new InvoiceCreated($id, $merchantId, $customerId, $subscriptionId, $period, $subtotal, $total));

        return $invoice;
    }

    /**
     * Rebuilds an Invoice from already-persisted data. Unlike create(),
     * this does not record an InvoiceCreated event.
     *
     * @param  InvoiceLine[]  $lines
     */
    public static function reconstitute(
        InvoiceId $id,
        MerchantId $merchantId,
        CustomerId $customerId,
        SubscriptionId $subscriptionId,
        BillingPeriod $period,
        array $lines,
        Money $subtotal,
        Money $total,
        InvoiceStatus $status,
        ?PaymentId $paymentId,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $voidedAt,
    ): self {
        return new self($id, $merchantId, $customerId, $subscriptionId, $period, $lines, $subtotal, $total, $status, $paymentId, $paidAt, $voidedAt);
    }

    public function id(): InvoiceId
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

    public function subscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }

    public function period(): BillingPeriod
    {
        return $this->period;
    }

    /**
     * @return InvoiceLine[]
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }

    public function paymentId(): ?PaymentId
    {
        return $this->paymentId;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function voidedAt(): ?DateTimeImmutable
    {
        return $this->voidedAt;
    }

    /**
     * Marking an already-Paid invoice paid again is a silent no-op rather
     * than an error: payment.succeeded.v1 may be redelivered, and that
     * redelivery must not fail.
     */
    public function markPaid(PaymentId $paymentId, DateTimeImmutable $paidAt): void
    {
        if ($this->status === InvoiceStatus::Paid) {
            return;
        }

        if ($this->status !== InvoiceStatus::Open) {
            throw InvalidInvoiceTransition::forAction($this->id, 'be marked paid', $this->status);
        }

        $this->status = InvoiceStatus::Paid;
        $this->paymentId = $paymentId;
        $this->paidAt = $paidAt;

        $this->recordEvent(new InvoicePaid($this->id, $paymentId, $paidAt));
    }

    public function void(DateTimeImmutable $voidedAt): void
    {
        if ($this->status === InvoiceStatus::Void) {
            throw InvoiceAlreadyVoided::withId($this->id);
        }

        if ($this->status !== InvoiceStatus::Open) {
            throw InvalidInvoiceTransition::forAction($this->id, 'be voided', $this->status);
        }

        $this->status = InvoiceStatus::Void;
        $this->voidedAt = $voidedAt;

        $this->recordEvent(new InvoiceVoided($this->id, $voidedAt));
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
