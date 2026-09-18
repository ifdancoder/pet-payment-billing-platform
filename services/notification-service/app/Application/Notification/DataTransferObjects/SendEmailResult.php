<?php

namespace App\Application\Notification\DataTransferObjects;

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
