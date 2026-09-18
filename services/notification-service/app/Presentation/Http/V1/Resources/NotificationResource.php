<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Notification\DeliveryAttempt;
use App\Domain\Notification\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function __construct(private readonly Notification $notification)
    {
        parent::__construct($notification);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->notification->id()->toString(),
            'merchant_id' => $this->notification->merchantId()->toString(),
            'source_event_id' => $this->notification->sourceEventId(),
            'type' => $this->notification->type()->label(),
            'channel' => $this->notification->channel()->label(),
            'recipient' => $this->notification->recipient()->toString(),
            'subject' => $this->notification->subject(),
            'status' => $this->notification->status()->label(),
            'sent_at' => $this->notification->sentAt()?->format(DATE_ATOM),
            'failed_at' => $this->notification->failedAt()?->format(DATE_ATOM),
            'attempts' => array_map($this->attemptToArray(...), $this->notification->attempts()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptToArray(DeliveryAttempt $attempt): array
    {
        return [
            'id' => $attempt->id()->toString(),
            'provider' => $attempt->provider(),
            'provider_reference' => $attempt->providerReference()?->toString(),
            'status' => $attempt->status()->label(),
            'failure_code' => $attempt->failureCode(),
            'failure_message' => $attempt->failureMessage(),
            'started_at' => $attempt->startedAt()->format(DATE_ATOM),
            'completed_at' => $attempt->completedAt()?->format(DATE_ATOM),
        ];
    }
}
