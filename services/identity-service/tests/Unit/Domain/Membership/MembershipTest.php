<?php

use App\Domain\Membership\Events\MembershipAdded;
use App\Domain\Membership\Events\MembershipRoleChanged;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;

test('create exposes the given data', function () {
    $id = MembershipId::generate();
    $userId = UserId::generate();
    $merchantId = MerchantId::generate();

    $membership = Membership::create($id, $userId, $merchantId, Role::Developer);

    expect($membership->id()->equals($id))->toBeTrue()
        ->and($membership->userId()->equals($userId))->toBeTrue()
        ->and($membership->merchantId()->equals($merchantId))->toBeTrue()
        ->and($membership->role())->toBe(Role::Developer);
});

test('create records a MembershipAdded event', function () {
    $userId = UserId::generate();
    $merchantId = MerchantId::generate();

    $membership = Membership::create(MembershipId::generate(), $userId, $merchantId, Role::Owner);

    $events = $membership->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(MembershipAdded::class)
        ->and($events[0]->userId->equals($userId))->toBeTrue()
        ->and($events[0]->merchantId->equals($merchantId))->toBeTrue()
        ->and($events[0]->role)->toBe(Role::Owner);
});

test('changeRole updates the role and records a MembershipRoleChanged event', function () {
    $membership = Membership::create(MembershipId::generate(), UserId::generate(), MerchantId::generate(), Role::Viewer);
    $membership->pullRecordedEvents();

    $membership->changeRole(Role::Admin);

    expect($membership->role())->toBe(Role::Admin);
    $events = $membership->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(MembershipRoleChanged::class)
        ->and($events[0]->previousRole)->toBe(Role::Viewer)
        ->and($events[0]->newRole)->toBe(Role::Admin);
});

test('changeRole is a no-op and records no event when the role is unchanged', function () {
    $membership = Membership::create(MembershipId::generate(), UserId::generate(), MerchantId::generate(), Role::Viewer);
    $membership->pullRecordedEvents();

    $membership->changeRole(Role::Viewer);

    expect($membership->role())->toBe(Role::Viewer)
        ->and($membership->pullRecordedEvents())->toBe([]);
});

test('pullRecordedEvents empties the recorded events', function () {
    $membership = Membership::create(MembershipId::generate(), UserId::generate(), MerchantId::generate(), Role::Owner);

    $membership->pullRecordedEvents();

    expect($membership->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data without recording an event', function () {
    $id = MembershipId::generate();

    $membership = Membership::reconstitute($id, UserId::generate(), MerchantId::generate(), Role::Finance);

    expect($membership->id()->equals($id))->toBeTrue()
        ->and($membership->role())->toBe(Role::Finance)
        ->and($membership->pullRecordedEvents())->toBe([]);
});
