<?php

namespace App\Infrastructure\Notification\Adapters\Messaging\Consumers;

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Ports\Outbound\ICustomerContactGatewayPort;
use App\Application\Notification\Ports\Outbound\ITemplateRendererPort;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationType;

/**
 * Translates a decoded payment.succeeded.v1 message into a rendered
 * email receipt and a CreateNotification command. Has no idea RabbitMQ
 * exists — it takes plain data, so it's testable without an AMQPMessage
 * at all.
 */
final class PaymentSucceededConsumer
{
    public function __construct(
        private readonly ICustomerContactGatewayPort $customerContacts,
        private readonly ITemplateRendererPort $templateRenderer,
        private readonly CreateNotificationHandler $createHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $contact = $this->customerContacts->find($payload['customer_id']);

        // No contact to notify — e.g. the customer was since deleted, or
        // customer-service is unreachable. Neither the Inbox nor a
        // Notification is recorded, so a redelivery of this same event
        // will simply retry the lookup.
        if ($contact === null) {
            return;
        }

        $rendered = $this->templateRenderer->render(NotificationType::PaymentReceipt, [
            'customerName' => $contact->name,
            'amount' => number_format($payload['amount_minor_units'] / 100, 2),
            'currency' => $payload['currency'],
            'paymentId' => $payload['payment_id'],
            'paidAt' => $payload['paid_at'],
        ]);

        $this->createHandler->handle(new CreateNotificationCommand(
            $eventId,
            'payment.succeeded.v1',
            $payload['merchant_id'],
            NotificationType::PaymentReceipt->value,
            NotificationChannel::Email->value,
            $contact->email,
            $rendered->subject,
            $rendered->bodyText,
            $rendered->bodyHtml,
            "payment_receipt:{$payload['payment_id']}:{$contact->email}",
        ));
    }
}
