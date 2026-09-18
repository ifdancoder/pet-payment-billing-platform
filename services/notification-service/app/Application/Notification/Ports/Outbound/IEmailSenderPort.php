<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailResult;

interface IEmailSenderPort
{
    public function send(SendEmailRequest $request): SendEmailResult;
}
