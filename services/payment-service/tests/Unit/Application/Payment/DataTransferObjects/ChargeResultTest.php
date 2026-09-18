<?php

use App\Application\Payment\DataTransferObjects\ChargeResult;
use App\Application\Payment\DataTransferObjects\ChargeStatus;

test('succeeded exposes the Succeeded status and provider reference', function () {
    $result = ChargeResult::succeeded('pi_123');

    expect($result->status)->toBe(ChargeStatus::Succeeded)
        ->and($result->providerReference)->toBe('pi_123')
        ->and($result->failureCode)->toBeNull();
});

test('failed exposes the Failed status and failure code', function () {
    $result = ChargeResult::failed('card_declined');

    expect($result->status)->toBe(ChargeStatus::Failed)
        ->and($result->failureCode)->toBe('card_declined')
        ->and($result->providerReference)->toBeNull();
});

test('pending exposes the Pending status and provider reference', function () {
    $result = ChargeResult::pending('pi_123');

    expect($result->status)->toBe(ChargeStatus::Pending)
        ->and($result->providerReference)->toBe('pi_123')
        ->and($result->failureCode)->toBeNull();
});
