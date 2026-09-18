<?php

use App\Domain\Notification\ValueObjects\NotificationType;

test('label returns the expected string for each case', function (NotificationType $type, string $label) {
    expect($type->label())->toBe($label);
})->with([
    [NotificationType::PaymentReceipt, 'payment_receipt'],
]);
