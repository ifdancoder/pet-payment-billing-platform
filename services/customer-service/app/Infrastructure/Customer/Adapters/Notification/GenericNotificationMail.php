<?php

namespace App\Infrastructure\Customer\Adapters\Notification;

use Illuminate\Mail\Mailable;

final class GenericNotificationMail extends Mailable
{
    public function __construct(string $mailSubject, string $body)
    {
        $this->subject($mailSubject)->html($body);
    }
}
