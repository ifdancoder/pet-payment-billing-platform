<?php

namespace App\Application\Notification\DataTransferObjects;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;

final readonly class SendEmailRequest
{
    public function __construct(
        /**
         * Used as the provider idempotency key, where the provider
         * supports one. A retry of the same attempt must reuse this exact
         * id, never generate a new one — otherwise the provider sends the
         * email twice instead of safely no-op-ing the repeat.
         */
        public DeliveryAttemptId $attemptId,
        public EmailAddress $recipient,
        public string $subject,
        public string $bodyText,
        public string $bodyHtml,
    ) {}
}
