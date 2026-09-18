<?php

use App\Domain\Payment\Exceptions\InvalidProviderReference;
use App\Domain\Payment\ValueObjects\ProviderReference;

test('it exposes the given value', function () {
    $reference = ProviderReference::of('pi_3Qxxxxxxxxxxxxx');

    expect($reference->toString())->toBe('pi_3Qxxxxxxxxxxxxx');
});

test('it throws when the value is empty', function () {
    ProviderReference::of('');
})->throws(InvalidProviderReference::class, 'A provider reference must not be empty.');

test('two references with the same value are equal', function () {
    $a = ProviderReference::of('pi_3Qxxxxxxxxxxxxx');
    $b = ProviderReference::of('pi_3Qxxxxxxxxxxxxx');

    expect($a->equals($b))->toBeTrue();
});

test('two references with different values are not equal', function () {
    $a = ProviderReference::of('pi_3Qxxxxxxxxxxxxx');
    $b = ProviderReference::of('pi_3Qyyyyyyyyyyyyy');

    expect($a->equals($b))->toBeFalse();
});

test('it can be cast to a string', function () {
    $reference = ProviderReference::of('pi_3Qxxxxxxxxxxxxx');

    expect((string) $reference)->toBe('pi_3Qxxxxxxxxxxxxx');
});
