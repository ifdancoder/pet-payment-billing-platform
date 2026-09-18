<?php

namespace App\Infrastructure\Notification\Adapters\EmailSender\Fake;

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailResult;
use App\Application\Notification\Ports\Outbound\IEmailSenderPort;

/**
 * A deterministic, no-network stand-in for a real provider (SES/SMTP).
 * Bound in local/testing environments so the whole
 * CreateNotification -> DeliverNotification -> Sent flow can be
 * exercised end to end without sending a real email.
 */
final class FakeEmailSender implements IEmailSenderPort
{
    public function send(SendEmailRequest $request): SendEmailResult
    {
        return SendEmailResult::succeeded("fake:{$request->attemptId->toString()}");
    }
}
