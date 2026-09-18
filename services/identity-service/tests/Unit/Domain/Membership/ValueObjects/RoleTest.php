<?php

use App\Domain\Membership\ValueObjects\Role;

test('label returns the expected string for each case', function (Role $role, string $label) {
    expect($role->label())->toBe($label);
})->with([
    [Role::Owner, 'owner'],
    [Role::Admin, 'admin'],
    [Role::Developer, 'developer'],
    [Role::Finance, 'finance'],
    [Role::Viewer, 'viewer'],
]);
