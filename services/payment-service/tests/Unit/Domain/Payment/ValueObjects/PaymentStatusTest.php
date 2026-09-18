<?php

use App\Domain\Payment\ValueObjects\PaymentStatus;

test('it can be created from a known status', function () {
    $status = PaymentStatus::from(1);

    expect($status)->toBe(PaymentStatus::Pending);
});

test('it throws when the status is unknown', function () {
    PaymentStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (PaymentStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [PaymentStatus::Pending, 'pending'],
    [PaymentStatus::Processing, 'processing'],
    [PaymentStatus::Succeeded, 'succeeded'],
    [PaymentStatus::Failed, 'failed'],
]);
