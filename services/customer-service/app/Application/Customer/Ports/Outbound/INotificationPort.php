<?php

namespace App\Application\Customer\Ports\Outbound;

use App\Domain\Customer\ValueObjects\Email;

interface INotificationPort
{
    public function send(Email $recipient, string $subject, string $body): void;
}
