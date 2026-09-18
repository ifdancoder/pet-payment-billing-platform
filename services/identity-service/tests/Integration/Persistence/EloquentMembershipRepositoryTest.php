<?php

use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Membership\Adapters\Persistence\Mappers\MembershipMapper;
use App\Infrastructure\Membership\Adapters\Persistence\Models\MembershipModel;
use App\Infrastructure\Membership\Adapters\Persistence\Repositories\EloquentMembershipRepository;
use App\Infrastructure\Merchant\Adapters\Persistence\Mappers\MerchantMapper;
use App\Infrastructure\Merchant\Adapters\Persistence\Repositories\EloquentMerchantRepository;
use App\Infrastructure\User\Adapters\Persistence\Mappers\UserMapper;
use App\Infrastructure\User\Adapters\Persistence\Repositories\EloquentUserRepository;

function aPersistedUserId(): UserId
{
    $user = User::register(UserId::generate(), Email::fromString(uniqid().'@example.com'), 'hashed-password');
    (new EloquentUserRepository(new UserMapper))->save($user);

    return $user->id();
}

function aPersistedMerchantId(): MerchantId
{
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    (new EloquentMerchantRepository(new MerchantMapper))->save($merchant);

    return $merchant->id();
}

test('save persists a new membership', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $membership = Membership::create(MembershipId::generate(), aPersistedUserId(), aPersistedMerchantId(), Role::Owner);

    $repository->save($membership);

    expect(MembershipModel::query()->where('id', $membership->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted membership instead of duplicating it', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $id = MembershipId::generate();
    $userId = aPersistedUserId();
    $merchantId = aPersistedMerchantId();
    $repository->save(Membership::create($id, $userId, $merchantId, Role::Viewer));

    $repository->save(Membership::reconstitute($id, $userId, $merchantId, Role::Admin));

    expect(MembershipModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(MembershipModel::query()->find($id->toString())->role)->toBe(Role::Admin->value);
});

test('get returns the matching membership', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $id = MembershipId::generate();
    $repository->save(Membership::create($id, aPersistedUserId(), aPersistedMerchantId(), Role::Developer));

    $found = $repository->get($id);

    expect($found->id()->equals($id))->toBeTrue()
        ->and($found->role())->toBe(Role::Developer);
});

test('get throws MembershipNotFound when no membership matches', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);

    $repository->get(MembershipId::generate());
})->throws(MembershipNotFound::class);

test('findByUserAndMerchant returns the matching membership', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $userId = aPersistedUserId();
    $merchantId = aPersistedMerchantId();
    $membership = Membership::create(MembershipId::generate(), $userId, $merchantId, Role::Finance);
    $repository->save($membership);

    $found = $repository->findByUserAndMerchant($userId, $merchantId);

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($membership->id()))->toBeTrue();
});

test('findByUserAndMerchant returns null when no membership matches', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);

    expect($repository->findByUserAndMerchant(aPersistedUserId(), aPersistedMerchantId()))->toBeNull();
});

test('listByUser returns every membership for the given user', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $userId = aPersistedUserId();
    $repository->save(Membership::create(MembershipId::generate(), $userId, aPersistedMerchantId(), Role::Owner));
    $repository->save(Membership::create(MembershipId::generate(), $userId, aPersistedMerchantId(), Role::Viewer));
    $repository->save(Membership::create(MembershipId::generate(), aPersistedUserId(), aPersistedMerchantId(), Role::Owner));

    expect($repository->listByUser($userId))->toHaveCount(2);
});

test('listByMerchant returns every membership for the given merchant', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $merchantId = aPersistedMerchantId();
    $repository->save(Membership::create(MembershipId::generate(), aPersistedUserId(), $merchantId, Role::Owner));
    $repository->save(Membership::create(MembershipId::generate(), aPersistedUserId(), $merchantId, Role::Developer));
    $repository->save(Membership::create(MembershipId::generate(), aPersistedUserId(), aPersistedMerchantId(), Role::Owner));

    expect($repository->listByMerchant($merchantId))->toHaveCount(2);
});

test('delete removes the membership', function () {
    $repository = new EloquentMembershipRepository(new MembershipMapper);
    $id = MembershipId::generate();
    $repository->save(Membership::create($id, aPersistedUserId(), aPersistedMerchantId(), Role::Owner));

    $repository->delete($id);

    expect(MembershipModel::query()->where('id', $id->toString())->exists())->toBeFalse();
});
