<?php

use App\Domain\Notification\ValueObjects\DeliveryAttemptStatus;

test('label returns the expected string for each case', function (DeliveryAttemptStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [DeliveryAttemptStatus::Pending, 'pending'],
    [DeliveryAttemptStatus::Succeeded, 'succeeded'],
    [DeliveryAttemptStatus::Failed, 'failed'],
]);
