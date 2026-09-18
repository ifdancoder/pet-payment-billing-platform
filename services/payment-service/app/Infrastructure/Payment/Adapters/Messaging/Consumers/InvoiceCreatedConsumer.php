<?php

namespace App\Infrastructure\Payment\Adapters\Messaging\Consumers;

use App\Application\Payment\Commands\CreatePayment\CreatePaymentCommand;
use App\Application\Payment\Commands\CreatePayment\CreatePaymentHandler;
use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentCommand;
use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentHandler;
use App\Domain\Payment\ValueObjects\PaymentStatus;

/**
 * Translates a decoded invoice.created.v1 message into CreatePayment,
 * then immediately kicks off the first ProcessPayment attempt. Has no
 * idea RabbitMQ exists — it takes plain data, so it's testable without
 * an AMQPMessage at all.
 */
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
        ));

        // null means this exact event was already processed — nothing
        // more to do. A non-Pending payment means findByInvoiceId
        // resolved to one that's already past its first attempt (e.g. a
        // second invoice.created.v1 for the same invoice); processing it
        // again here would be wrong — that's what an explicit retry is for.
        if ($payment === null || $payment->status() !== PaymentStatus::Pending) {
            return;
        }

        $this->processHandler->handle(new ProcessPaymentCommand($payment->id()->toString(), $payment->merchantId()->toString()));
    }
}
