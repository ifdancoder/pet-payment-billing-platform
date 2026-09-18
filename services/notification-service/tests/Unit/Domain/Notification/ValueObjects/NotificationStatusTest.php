<?php

use App\Domain\Notification\ValueObjects\NotificationStatus;

test('label returns the expected string for each case', function (NotificationStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [NotificationStatus::Pending, 'pending'],
    [NotificationStatus::Processing, 'processing'],
    [NotificationStatus::Sent, 'sent'],
    [NotificationStatus::Failed, 'failed'],
]);
