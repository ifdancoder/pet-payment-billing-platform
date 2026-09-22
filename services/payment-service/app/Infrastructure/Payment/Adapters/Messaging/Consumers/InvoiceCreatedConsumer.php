<?php

namespace App\Infrastructure\Payment\Adapters\Messaging\Consumers;

use App\Application\Payment\Commands\CreatePayment\CreatePaymentCommand;
use App\Application\Payment\Commands\CreatePayment\CreatePaymentHandler;
use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentCommand;
use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentHandler;
use App\Domain\Payment\ValueObjects\PaymentStatus;

final class InvoiceCreatedConsumer
{
    public function __construct(
        private readonly CreatePaymentHandler $createHandler,
        private readonly ProcessPaymentHandler $processHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $payment = $this->createHandler->handle(new CreatePaymentCommand(
            $eventId,
            'invoice.created.v1',
            $payload['invoice_id'],
            $payload['merchant_id'],
            $payload['customer_id'],
            $payload['amount_minor_units'],
            $payload['currency'],
            $payload['billing_reason'] ?? 'subscription_create',
        ));

        // Redelivery must not start another attempt for an existing payment.
        if ($payment === null || $payment->status() !== PaymentStatus::Pending) {
            return;
        }

        $this->processHandler->handle(new ProcessPaymentCommand($payment->id()->toString(), $payment->merchantId()->toString()));
    }
}
