<?php

namespace App\Domain\Payment;

use App\Domain\Payment\Exceptions\InvalidPaymentAttemptTransition;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentAttemptStatus;
use App\Domain\Payment\ValueObjects\ProviderReference;
use DateTimeImmutable;

final class PaymentAttempt
{
    private function __construct(
        private readonly PaymentAttemptId $id,
        private readonly string $provider,
        private ?ProviderReference $providerReference,
        private PaymentAttemptStatus $status,
        private ?string $failureCode,
        private ?string $failureMessage,
        private readonly DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt,
    ) {}

    public static function start(PaymentAttemptId $id, string $provider, DateTimeImmutable $startedAt): self
    {
        return new self($id, $provider, null, PaymentAttemptStatus::Pending, null, null, $startedAt, null);
    }

    public static function reconstitute(
        PaymentAttemptId $id,
        string $provider,
        ?ProviderReference $providerReference,
        PaymentAttemptStatus $status,
        ?string $failureCode,
        ?string $failureMessage,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
    ): self {
        return new self($id, $provider, $providerReference, $status, $failureCode, $failureMessage, $startedAt, $completedAt);
    }

    public function id(): PaymentAttemptId
    {
        return $this->id;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function providerReference(): ?ProviderReference
    {
        return $this->providerReference;
    }

    public function status(): PaymentAttemptStatus
    {
        return $this->status;
    }

    public function failureCode(): ?string
    {
        return $this->failureCode;
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function succeed(ProviderReference $reference, DateTimeImmutable $completedAt): void
    {
        if ($this->status !== PaymentAttemptStatus::Pending) {
            throw InvalidPaymentAttemptTransition::forAction($this->id, 'succeed', $this->status);
        }

        $this->status = PaymentAttemptStatus::Succeeded;
        $this->providerReference = $reference;
        $this->completedAt = $completedAt;
    }

    public function fail(string $failureCode, ?string $failureMessage, DateTimeImmutable $completedAt): void
    {
        if ($this->status !== PaymentAttemptStatus::Pending) {
            throw InvalidPaymentAttemptTransition::forAction($this->id, 'fail', $this->status);
        }

        $this->status = PaymentAttemptStatus::Failed;
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
        $this->completedAt = $completedAt;
    }
}
