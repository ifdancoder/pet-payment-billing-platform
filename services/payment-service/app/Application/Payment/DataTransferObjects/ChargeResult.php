<?php

namespace App\Application\Payment\DataTransferObjects;

/**
 * The boundary DTO a PaymentGateway adapter returns. Deliberately plain
 * primitives, not domain value objects — this is what comes back from
 * the provider before anything has been translated into our own
 * ProviderReference/PaymentAttempt vocabulary. That translation is the
 * caller's job (ProcessPaymentHandler), not the gateway's.
 */
final readonly class ChargeResult
{
    private function __construct(
        public ChargeStatus $status,
        public ?string $providerReference,
        public ?string $failureCode,
    ) {}

    public static function succeeded(string $providerReference): self
    {
        return new self(ChargeStatus::Succeeded, $providerReference, null);
    }

    public static function failed(string $failureCode): self
    {
        return new self(ChargeStatus::Failed, null, $failureCode);
    }

    /**
     * Some payment methods don't return a synchronous final result — the
     * provider will confirm success or failure later via webhook.
     */
    public static function pending(string $providerReference): self
    {
        return new self(ChargeStatus::Pending, $providerReference, null);
    }
}
