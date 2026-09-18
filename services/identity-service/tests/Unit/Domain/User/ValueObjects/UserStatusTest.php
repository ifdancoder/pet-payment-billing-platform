<?php

use App\Domain\User\ValueObjects\UserStatus;

test('label returns the expected string for each case', function (UserStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [UserStatus::Active, 'active'],
    [UserStatus::Disabled, 'disabled'],
]);
