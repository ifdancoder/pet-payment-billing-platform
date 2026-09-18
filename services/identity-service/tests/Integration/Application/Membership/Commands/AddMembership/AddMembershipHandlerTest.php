<?php

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\AddMembership\AddMembershipHandler;
use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Exceptions\UserAlreadyAMember;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Merchant\Adapters\Persistence\Mappers\MerchantMapper;
use App\Infrastructure\Merchant\Adapters\Persistence\Repositories\EloquentMerchantRepository;
use App\Infrastructure\User\Adapters\Persistence\Mappers\UserMapper;
use App\Infrastructure\User\Adapters\Persistence\Repositories\EloquentUserRepository;

function aPersistedUser(): User
{
    $user = User::register(UserId::generate(), Email::fromString(uniqid().'@example.com'), 'hashed-password');
    (new EloquentUserRepository(new UserMapper))->save($user);

    return $user;
}

function aPersistedMerchant(): Merchant
{
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);

    return $merchant;
}

test('handle adds a membership with the given role', function () {
    $user = aPersistedUser();
    $merchant = aPersistedMerchant();

    $membership = app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Owner->value));

    expect($membership->userId()->equals($user->id()))->toBeTrue()
        ->and($membership->merchantId()->equals($merchant->id()))->toBeTrue()
        ->and($membership->role())->toBe(Role::Owner);
});

test('handle persists the membership', function () {
    $user = aPersistedUser();
    $merchant = aPersistedMerchant();

    $membership = app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Owner->value));

    $persisted = app(IMembershipRepositoryPort::class)->get($membership->id());
    expect($persisted->id()->equals($membership->id()))->toBeTrue();
});

test('handle throws UserAlreadyAMember when the user already belongs to the merchant', function () {
    $user = aPersistedUser();
    $merchant = aPersistedMerchant();
    app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Owner->value));

    app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Viewer->value));
})->throws(UserAlreadyAMember::class);
