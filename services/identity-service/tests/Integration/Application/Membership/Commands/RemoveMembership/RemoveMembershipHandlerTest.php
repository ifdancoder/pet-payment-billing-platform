<?php

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\AddMembership\AddMembershipHandler;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipCommand;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipHandler;
use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
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

test('handle removes the membership', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');
    (new EloquentUserRepository(new UserMapper))->save($user);
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);
    $membership = app(AddMembershipHandler::class)->handle(new AddMembershipCommand($user->id()->toString(), $merchant->id()->toString(), Role::Owner->value));

    app(RemoveMembershipHandler::class)->handle(new RemoveMembershipCommand($membership->id()->toString()));

    expect(fn () => app(IMembershipRepositoryPort::class)->get($membership->id()))->toThrow(MembershipNotFound::class);
});

test('handle throws MembershipNotFound when no membership matches', function () {
    app(RemoveMembershipHandler::class)->handle(new RemoveMembershipCommand(MembershipId::generate()->toString()));
})->throws(MembershipNotFound::class);
