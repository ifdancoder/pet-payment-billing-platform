<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailResult;

/**
 * The single seam between our business model and whatever provider
 * actually delivers the email. Scoped to exactly what this project's
 * use cases need — not a universal abstraction meant to fit every
 * provider or channel that might ever exist.
 */
interface IEmailSenderPort
{
    public function send(SendEmailRequest $request): SendEmailResult;
}
