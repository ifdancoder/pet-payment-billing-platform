<?php

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\AddMembership\AddMembershipHandler;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsHandler;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsQuery;
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

function registerAUserForListMembershipsTest(): User
{
    $user = User::register(UserId::generate(), Email::fromString(uniqid().'@example.com'), 'hashed-password');
    (new EloquentUserRepository(new UserMapper))->save($user);

    return $user;
}

test('handle returns every membership for the given merchant', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);
    $otherMerchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Other Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($otherMerchant);
    app(AddMembershipHandler::class)->handle(new AddMembershipCommand(registerAUserForListMembershipsTest()->id()->toString(), $merchant->id()->toString(), Role::Owner->value));
    app(AddMembershipHandler::class)->handle(new AddMembershipCommand(registerAUserForListMembershipsTest()->id()->toString(), $merchant->id()->toString(), Role::Developer->value));
    app(AddMembershipHandler::class)->handle(new AddMembershipCommand(registerAUserForListMembershipsTest()->id()->toString(), $otherMerchant->id()->toString(), Role::Owner->value));

    $memberships = app(ListMembershipsHandler::class)->handle(new ListMembershipsQuery($merchant->id()->toString()));

    expect($memberships)->toHaveCount(2);
});

test('handle returns an empty array when the merchant has no memberships', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);

    $memberships = app(ListMembershipsHandler::class)->handle(new ListMembershipsQuery($merchant->id()->toString()));

    expect($memberships)->toBe([]);
});
