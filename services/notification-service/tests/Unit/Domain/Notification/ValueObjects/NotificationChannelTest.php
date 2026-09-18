<?php

use App\Domain\Notification\ValueObjects\NotificationChannel;

test('label returns the expected string for each case', function (NotificationChannel $channel, string $label) {
    expect($channel->label())->toBe($label);
})->with([
    [NotificationChannel::Email, 'email'],
]);
