<?php

namespace App\Infrastructure\Notification\Adapters\Messaging\Consumers;

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Ports\Outbound\ICustomerContactGatewayPort;
use App\Application\Notification\Ports\Outbound\ITemplateRendererPort;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationType;

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
        $contact = $this->customerContacts->find($payload['merchant_id'], $payload['customer_id']);

        if ($contact === null) {
            // The inbox remains untouched so redelivery can retry the lookup.
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
