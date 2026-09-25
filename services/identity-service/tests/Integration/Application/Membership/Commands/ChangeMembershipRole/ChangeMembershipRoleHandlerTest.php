<?php

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\AddMembership\AddMembershipHandler;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleHandler;
use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\ValueObjects\MembershipId;
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

test('handle changes the role', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');
    (new EloquentUserRepository(new UserMapper))->save($user);
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);
    $membership = app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Viewer->value));

    $changed = app(ChangeMembershipRoleHandler::class)->handle(new ChangeMembershipRoleCommand($merchant->id()->toString(), $membership->id()->toString(), Role::Admin->value));

    expect($changed->role())->toBe(Role::Admin);
});

test('handle throws MembershipNotFound when no membership matches', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Missing Membership Merchant'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);
    app(ChangeMembershipRoleHandler::class)->handle(new ChangeMembershipRoleCommand($merchant->id()->toString(), MembershipId::generate()->toString(), Role::Admin->value));
})->throws(MembershipNotFound::class);
