<?php

use App\Domain\Subscription\ValueObjects\SubscriptionStatus;

test('it can be created from a known status', function () {
    $status = SubscriptionStatus::from(1);

    expect($status)->toBe(SubscriptionStatus::Pending);
});

test('it throws when the status is unknown', function () {
    SubscriptionStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (SubscriptionStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [SubscriptionStatus::Pending, 'pending'],
    [SubscriptionStatus::Active, 'active'],
    [SubscriptionStatus::PastDue, 'past_due'],
    [SubscriptionStatus::Canceled, 'canceled'],
]);
