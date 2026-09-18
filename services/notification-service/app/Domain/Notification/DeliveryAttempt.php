<?php

namespace App\Domain\Notification;

use App\Domain\Notification\Exceptions\InvalidDeliveryAttemptTransition;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\DeliveryAttemptStatus;
use App\Domain\Notification\ValueObjects\ProviderReference;
use DateTimeImmutable;

/**
 * An entity within the Notification aggregate. It has no independent
 * lifecycle: it is never fetched, saved, or modified on its own, only
 * ever as part of its owning Notification. A single Notification may
 * hold several of these across retries — the attempts are the technical
 * history, the Notification's own status is the business outcome.
 */
final class DeliveryAttempt
{
    private function __construct(
        private readonly DeliveryAttemptId $id,
        private readonly string $provider,
        private ?ProviderReference $providerReference,
        private DeliveryAttemptStatus $status,
        private ?string $failureCode,
        private ?string $failureMessage,
        private readonly DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt,
    ) {}

    public static function start(DeliveryAttemptId $id, string $provider, DateTimeImmutable $startedAt): self
    {
        return new self($id, $provider, null, DeliveryAttemptStatus::Pending, null, null, $startedAt, null);
    }

    /**
     * Rebuilds a DeliveryAttempt from already-persisted data.
     */
    public static function reconstitute(
        DeliveryAttemptId $id,
        string $provider,
        ?ProviderReference $providerReference,
        DeliveryAttemptStatus $status,
        ?string $failureCode,
        ?string $failureMessage,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
    ): self {
        return new self($id, $provider, $providerReference, $status, $failureCode, $failureMessage, $startedAt, $completedAt);
    }

    public function id(): DeliveryAttemptId
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

    public function status(): DeliveryAttemptStatus
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
        if ($this->status !== DeliveryAttemptStatus::Pending) {
            throw InvalidDeliveryAttemptTransition::forAction($this->id, 'succeed', $this->status);
        }

        $this->status = DeliveryAttemptStatus::Succeeded;
        $this->providerReference = $reference;
        $this->completedAt = $completedAt;
    }

    public function fail(string $failureCode, ?string $failureMessage, DateTimeImmutable $completedAt): void
    {
        if ($this->status !== DeliveryAttemptStatus::Pending) {
            throw InvalidDeliveryAttemptTransition::forAction($this->id, 'fail', $this->status);
        }

        $this->status = DeliveryAttemptStatus::Failed;
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
        $this->completedAt = $completedAt;
    }
}
