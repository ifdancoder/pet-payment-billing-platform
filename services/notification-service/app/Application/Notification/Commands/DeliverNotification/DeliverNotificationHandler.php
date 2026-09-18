<?php

namespace App\Application\Notification\Commands\DeliverNotification;

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailStatus;
use App\Application\Notification\Ports\Outbound\IEmailSenderPort;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

/**
 * The one handler in this service that talks to the network. The DB
 * transaction never spans that call: a short transaction starts the
 * attempt and commits, then the sender is called with no transaction
 * open at all, then a second short transaction applies whatever the
 * sender said.
 */
final class DeliverNotificationHandler
{
    /**
     * Which provider is sending — hardcoded until a second one (SES/SMTP)
     * exists to actually choose between.
     */
    private const string PROVIDER = 'fake';

    public function __construct(
        private readonly INotificationRepositoryPort $repository,
        private readonly IEmailSenderPort $sender,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(DeliverNotificationCommand $command): Notification
    {
        $notificationId = NotificationId::fromString($command->notificationId);
        $merchantId = MerchantId::fromString($command->merchantId);
        $attemptId = DeliveryAttemptId::generate();

        $notification = $this->repository->get($notificationId, $merchantId);

        $this->transaction->run(function () use ($notification, $attemptId): void {
            $notification->startDelivery($attemptId, self::PROVIDER, new DateTimeImmutable);
            $this->repository->save($notification);
        });

        $result = $this->sender->send(new SendEmailRequest(
            $attemptId,
            $notification->recipient(),
            $notification->subject(),
            $notification->bodyText(),
            $notification->bodyHtml(),
        ));

        $this->transaction->run(function () use ($notification, $attemptId, $result): void {
            match ($result->status) {
                SendEmailStatus::Succeeded => $notification->markSent(
                    $attemptId,
                    ProviderReference::of($result->providerReference),
                    new DateTimeImmutable,
                ),
                SendEmailStatus::Failed => $notification->markFailed($attemptId, $result->failureCode, null, new DateTimeImmutable),
            };

            $this->repository->save($notification);
        });

        return $notification;
    }
}
