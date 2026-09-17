<?php

use App\Domain\Invoice\Exceptions\InvalidBillingPeriod;
use App\Domain\Invoice\ValueObjects\BillingPeriod;

test('it exposes the start and end', function () {
    $start = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $end = new DateTimeImmutable('2026-10-01T00:00:00+00:00');

    $period = BillingPeriod::of($start, $end);

    expect($period->start())->toEqual($start)
        ->and($period->end())->toEqual($end);
});

test('it throws when the end is before the start', function () {
    BillingPeriod::of(
        new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
    );
})->throws(InvalidBillingPeriod::class, 'A billing period must end after it starts.');

test('it throws when the end equals the start', function () {
    $moment = new DateTimeImmutable('2026-09-01T00:00:00+00:00');

    BillingPeriod::of($moment, $moment);
})->throws(InvalidBillingPeriod::class, 'A billing period must end after it starts.');

test('two periods with the same start and end are equal', function () {
    $start = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $end = new DateTimeImmutable('2026-10-01T00:00:00+00:00');

    $a = BillingPeriod::of($start, $end);
    $b = BillingPeriod::of($start, $end);

    expect($a->equals($b))->toBeTrue();
});

test('two periods with a different start or end are not equal', function () {
    $start = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $end = new DateTimeImmutable('2026-10-01T00:00:00+00:00');
    $other = new DateTimeImmutable('2026-11-01T00:00:00+00:00');

    $a = BillingPeriod::of($start, $end);
    $b = BillingPeriod::of($start, $other);

    expect($a->equals($b))->toBeFalse();
});
