<?php

use App\Domain\Notification\Exceptions\InvalidProviderReference;
use App\Domain\Notification\ValueObjects\ProviderReference;

test('it exposes the given value', function () {
    $reference = ProviderReference::of('0102018f-ses-message-id');

    expect($reference->toString())->toBe('0102018f-ses-message-id');
});

test('it throws when the value is empty', function () {
    ProviderReference::of('');
})->throws(InvalidProviderReference::class, 'A provider reference must not be empty.');

test('two references with the same value are equal', function () {
    $a = ProviderReference::of('0102018f-ses-message-id');
    $b = ProviderReference::of('0102018f-ses-message-id');

    expect($a->equals($b))->toBeTrue();
});

test('two references with different values are not equal', function () {
    $a = ProviderReference::of('0102018f-ses-message-id');
    $b = ProviderReference::of('0102018f-other-message-id');

    expect($a->equals($b))->toBeFalse();
});

test('it can be cast to a string', function () {
    $reference = ProviderReference::of('0102018f-ses-message-id');

    expect((string) $reference)->toBe('0102018f-ses-message-id');
});
