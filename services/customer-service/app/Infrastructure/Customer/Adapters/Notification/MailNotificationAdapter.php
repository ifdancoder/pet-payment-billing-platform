<?php

namespace App\Infrastructure\Customer\Adapters\Notification;

use App\Application\Customer\Ports\Outbound\INotificationPort;
use App\Domain\Customer\ValueObjects\Email;
use Illuminate\Support\Facades\Mail;

final class MailNotificationAdapter implements INotificationPort
{
    public function send(Email $recipient, string $subject, string $body): void
    {
        Mail::to($recipient->toString())->send(new GenericNotificationMail($subject, $body));
    }
}
