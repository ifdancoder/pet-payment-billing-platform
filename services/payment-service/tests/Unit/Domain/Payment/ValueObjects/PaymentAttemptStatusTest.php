<?php

use App\Domain\Payment\ValueObjects\PaymentAttemptStatus;

test('it can be created from a known status', function () {
    $status = PaymentAttemptStatus::from(1);

    expect($status)->toBe(PaymentAttemptStatus::Pending);
});

test('it throws when the status is unknown', function () {
    PaymentAttemptStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (PaymentAttemptStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [PaymentAttemptStatus::Pending, 'pending'],
    [PaymentAttemptStatus::Succeeded, 'succeeded'],
    [PaymentAttemptStatus::Failed, 'failed'],
]);
