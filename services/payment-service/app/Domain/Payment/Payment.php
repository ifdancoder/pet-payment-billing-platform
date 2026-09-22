<?php

namespace App\Domain\Payment;

use App\Domain\Payment\Events\PaymentCreated;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\Events\PaymentSucceeded;
use App\Domain\Payment\Exceptions\InvalidPaymentTransition;
use App\Domain\Payment\Exceptions\PaymentAlreadyCompleted;
use App\Domain\Payment\Exceptions\PaymentAttemptNotFound;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class Payment
{
    /** @var array<object> */
    private array $recordedEvents = [];

    /** @var PaymentAttempt[] */
    private array $attempts;

    /**
     * @param  PaymentAttempt[]  $attempts
     */
    private function __construct(
        private readonly PaymentId $id,
        private readonly InvoiceId $invoiceId,
        private readonly MerchantId $merchantId,
        private readonly CustomerId $customerId,
        private readonly Money $money,
        private readonly string $billingReason,
        private PaymentStatus $status,
        array $attempts,
        private ?DateTimeImmutable $paidAt = null,
        private ?DateTimeImmutable $failedAt = null,
    ) {
        $this->attempts = $attempts;
    }

    public static function create(PaymentId $id, InvoiceId $invoiceId, MerchantId $merchantId, CustomerId $customerId, Money $money, string $billingReason = 'subscription_create'): self
    {
        $payment = new self($id, $invoiceId, $merchantId, $customerId, $money, $billingReason, PaymentStatus::Pending, []);
        $payment->recordEvent(new PaymentCreated($id, $invoiceId, $merchantId, $customerId, $money));

        return $payment;
    }

    /** @param PaymentAttempt[] $attempts */
    public static function reconstitute(
        PaymentId $id,
        InvoiceId $invoiceId,
        MerchantId $merchantId,
        CustomerId $customerId,
        Money $money,
        PaymentStatus $status,
        array $attempts,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $failedAt,
        string $billingReason = 'subscription_create',
    ): self {
        return new self($id, $invoiceId, $merchantId, $customerId, $money, $billingReason, $status, $attempts, $paidAt, $failedAt);
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function invoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function money(): Money
    {
        return $this->money;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function billingReason(): string
    {
        return $this->billingReason;
    }

    /**
     * @return PaymentAttempt[]
     */
    public function attempts(): array
    {
        return $this->attempts;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    /** Starts an initial attempt or retries a failed payment. */
    public function startAttempt(PaymentAttemptId $attemptId, string $provider, DateTimeImmutable $now): PaymentAttempt
    {
        if ($this->status === PaymentStatus::Succeeded) {
            throw PaymentAlreadyCompleted::withId($this->id);
        }

        if (! in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            throw InvalidPaymentTransition::forAction($this->id, 'start a new attempt', $this->status);
        }

        $attempt = PaymentAttempt::start($attemptId, $provider, $now);
        $this->attempts[] = $attempt;
        $this->status = PaymentStatus::Processing;

        return $attempt;
    }

    /** Repeated success notifications are idempotent. */
    public function succeed(PaymentAttemptId $attemptId, ProviderReference $reference, DateTimeImmutable $at): void
    {
        if ($this->status === PaymentStatus::Succeeded) {
            return;
        }

        if ($this->status !== PaymentStatus::Processing) {
            throw InvalidPaymentTransition::forAction($this->id, 'succeed', $this->status);
        }

        $this->findAttempt($attemptId)->succeed($reference, $at);

        $this->status = PaymentStatus::Succeeded;
        $this->paidAt = $at;

        $this->recordEvent(new PaymentSucceeded($this->id, $this->invoiceId, $this->merchantId, $this->customerId, $this->money, $attemptId, $reference, $at));
    }

    public function fail(PaymentAttemptId $attemptId, string $failureCode, ?string $failureMessage, DateTimeImmutable $at): void
    {
        if ($this->status !== PaymentStatus::Processing) {
            throw InvalidPaymentTransition::forAction($this->id, 'fail', $this->status);
        }

        $this->findAttempt($attemptId)->fail($failureCode, $failureMessage, $at);

        $this->status = PaymentStatus::Failed;
        $this->failedAt = $at;

        $this->recordEvent(new PaymentFailed($this->id, $this->invoiceId, $this->merchantId, $this->customerId, $attemptId, $failureCode, $at));
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

    private function findAttempt(PaymentAttemptId $attemptId): PaymentAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->id()->equals($attemptId)) {
                return $attempt;
            }
        }

        throw PaymentAttemptNotFound::withId($attemptId);
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
