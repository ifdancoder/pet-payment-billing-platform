<?php

namespace App\Application\Payment\DataTransferObjects;

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

    public static function pending(string $providerReference): self
    {
        return new self(ChargeStatus::Pending, $providerReference, null);
    }
}
