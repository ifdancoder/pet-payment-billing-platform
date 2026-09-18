<?php

namespace App\Application\Notification\DataTransferObjects;

/**
 * The boundary DTO an EmailSender adapter returns. Deliberately plain
 * primitives, not domain value objects — this is what comes back from
 * the provider before anything has been translated into our own
 * ProviderReference/DeliveryAttempt vocabulary. That translation is the
 * caller's job (DeliverNotificationHandler), not the sender's.
 */
final readonly class SendEmailResult
{
    private function __construct(
        public SendEmailStatus $status,
        public ?string $providerReference,
        public ?string $failureCode,
    ) {}

    public static function succeeded(string $providerReference): self
    {
        return new self(SendEmailStatus::Succeeded, $providerReference, null);
    }

    public static function failed(string $failureCode): self
    {
        return new self(SendEmailStatus::Failed, null, $failureCode);
    }
}
