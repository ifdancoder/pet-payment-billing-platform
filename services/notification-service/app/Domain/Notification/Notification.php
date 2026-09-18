<?php

namespace App\Domain\Notification;

use App\Domain\Notification\Events\NotificationCreated;
use App\Domain\Notification\Events\NotificationFailed;
use App\Domain\Notification\Events\NotificationSent;
use App\Domain\Notification\Exceptions\DeliveryAttemptNotFound;
use App\Domain\Notification\Exceptions\InvalidNotificationTransition;
use App\Domain\Notification\Exceptions\NotificationAlreadySent;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class Notification
{
    /** @var array<object> */
    private array $recordedEvents = [];

    /** @var DeliveryAttempt[] */
    private array $attempts;

    /**
     * @param  DeliveryAttempt[]  $attempts
     */
    private function __construct(
        private readonly NotificationId $id,
        private readonly MerchantId $merchantId,
        private readonly string $sourceEventId,
        private readonly NotificationType $type,
        private readonly NotificationChannel $channel,
        private readonly EmailAddress $recipient,
        private readonly string $subject,
        private readonly string $bodyText,
        private readonly string $bodyHtml,
        private readonly string $deduplicationKey,
        private NotificationStatus $status,
        array $attempts,
        private ?DateTimeImmutable $sentAt = null,
        private ?DateTimeImmutable $failedAt = null,
    ) {
        $this->attempts = $attempts;
    }

    public static function create(
        NotificationId $id,
        MerchantId $merchantId,
        string $sourceEventId,
        NotificationType $type,
        NotificationChannel $channel,
        EmailAddress $recipient,
        string $subject,
        string $bodyText,
        string $bodyHtml,
        string $deduplicationKey,
    ): self {
        $notification = new self($id, $merchantId, $sourceEventId, $type, $channel, $recipient, $subject, $bodyText, $bodyHtml, $deduplicationKey, NotificationStatus::Pending, []);
        $notification->recordEvent(new NotificationCreated($id, $merchantId, $sourceEventId, $type, $channel, $recipient, $deduplicationKey));

        return $notification;
    }

    /** @param DeliveryAttempt[] $attempts */
    public static function reconstitute(
        NotificationId $id,
        MerchantId $merchantId,
        string $sourceEventId,
        NotificationType $type,
        NotificationChannel $channel,
        EmailAddress $recipient,
        string $subject,
        string $bodyText,
        string $bodyHtml,
        string $deduplicationKey,
        NotificationStatus $status,
        array $attempts,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
    ): self {
        return new self($id, $merchantId, $sourceEventId, $type, $channel, $recipient, $subject, $bodyText, $bodyHtml, $deduplicationKey, $status, $attempts, $sentAt, $failedAt);
    }

    public function id(): NotificationId
    {
        return $this->id;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function sourceEventId(): string
    {
        return $this->sourceEventId;
    }

    public function type(): NotificationType
    {
        return $this->type;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function recipient(): EmailAddress
    {
        return $this->recipient;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function bodyText(): string
    {
        return $this->bodyText;
    }

    public function bodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function deduplicationKey(): string
    {
        return $this->deduplicationKey;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }

    /**
     * @return DeliveryAttempt[]
     */
    public function attempts(): array
    {
        return $this->attempts;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    /** Starts an initial delivery or retries a failed notification. */
    public function startDelivery(DeliveryAttemptId $attemptId, string $provider, DateTimeImmutable $now): DeliveryAttempt
    {
        if ($this->status === NotificationStatus::Sent) {
            throw NotificationAlreadySent::withId($this->id);
        }

        if (! in_array($this->status, [NotificationStatus::Pending, NotificationStatus::Failed], true)) {
            throw InvalidNotificationTransition::forAction($this->id, 'start a new delivery attempt', $this->status);
        }

        $attempt = DeliveryAttempt::start($attemptId, $provider, $now);
        $this->attempts[] = $attempt;
        $this->status = NotificationStatus::Processing;

        return $attempt;
    }

    /** Repeated success notifications are idempotent. */
    public function markSent(DeliveryAttemptId $attemptId, ProviderReference $reference, DateTimeImmutable $at): void
    {
        if ($this->status === NotificationStatus::Sent) {
            return;
        }

        if ($this->status !== NotificationStatus::Processing) {
            throw InvalidNotificationTransition::forAction($this->id, 'mark sent', $this->status);
        }

        $this->findAttempt($attemptId)->succeed($reference, $at);

        $this->status = NotificationStatus::Sent;
        $this->sentAt = $at;

        $this->recordEvent(new NotificationSent($this->id, $this->merchantId, $attemptId, $reference, $at));
    }

    public function markFailed(DeliveryAttemptId $attemptId, string $failureCode, ?string $failureMessage, DateTimeImmutable $at): void
    {
        if ($this->status !== NotificationStatus::Processing) {
            throw InvalidNotificationTransition::forAction($this->id, 'mark failed', $this->status);
        }

        $this->findAttempt($attemptId)->fail($failureCode, $failureMessage, $at);

        $this->status = NotificationStatus::Failed;
        $this->failedAt = $at;

        $this->recordEvent(new NotificationFailed($this->id, $this->merchantId, $attemptId, $failureCode, $at));
    }

    /**
     * @return array<object>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function findAttempt(DeliveryAttemptId $attemptId): DeliveryAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->id()->equals($attemptId)) {
                return $attempt;
            }
        }

        throw DeliveryAttemptNotFound::withId($attemptId);
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
