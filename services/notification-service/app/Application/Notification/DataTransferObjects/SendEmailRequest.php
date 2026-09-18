<?php

namespace App\Application\Notification\DataTransferObjects;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;

final readonly class SendEmailRequest
{
    public function __construct(

        public DeliveryAttemptId $attemptId,
        public EmailAddress $recipient,
        public string $subject,
        public string $bodyText,
        public string $bodyHtml,
    ) {}
}
