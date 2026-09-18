<?php

use App\Domain\Merchant\ValueObjects\MerchantStatus;

test('label returns the expected string for each case', function (MerchantStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [MerchantStatus::Active, 'active'],
    [MerchantStatus::Disabled, 'disabled'],
]);
