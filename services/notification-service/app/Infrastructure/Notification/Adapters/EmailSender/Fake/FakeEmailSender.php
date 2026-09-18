<?php

namespace App\Infrastructure\Notification\Adapters\EmailSender\Fake;

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailResult;
use App\Application\Notification\Ports\Outbound\IEmailSenderPort;

final class FakeEmailSender implements IEmailSenderPort
{
    public function send(SendEmailRequest $request): SendEmailResult
    {
        return SendEmailResult::succeeded("fake:{$request->attemptId->toString()}");
    }
}
