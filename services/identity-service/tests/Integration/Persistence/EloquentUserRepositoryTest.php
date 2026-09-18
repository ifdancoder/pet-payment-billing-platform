<?php

use App\Domain\User\Exceptions\UserNotFound;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserStatus;
use App\Infrastructure\User\Adapters\Persistence\Mappers\UserMapper;
use App\Infrastructure\User\Adapters\Persistence\Models\UserModel;
use App\Infrastructure\User\Adapters\Persistence\Repositories\EloquentUserRepository;

test('save persists a new user', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');

    $repository->save($user);

    expect(UserModel::query()->where('id', $user->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted user instead of duplicating it', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $id = UserId::generate();
    $repository->save(User::register($id, Email::fromString('alice@example.com'), 'old-hash'));

    $repository->save(User::reconstitute($id, Email::fromString('alice@example.com'), 'new-hash', UserStatus::Active));

    expect(UserModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(UserModel::query()->find($id->toString())->password_hash)->toBe('new-hash');
});

test('get returns the matching user', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $id = UserId::generate();
    $repository->save(User::register($id, Email::fromString('alice@example.com'), 'hashed-password'));

    $found = $repository->get($id);

    expect($found->id()->equals($id))->toBeTrue()
        ->and($found->email()->toString())->toBe('alice@example.com');
});

test('get throws UserNotFound when no user matches', function () {
    $repository = new EloquentUserRepository(new UserMapper);

    $repository->get(UserId::generate());
})->throws(UserNotFound::class);

test('findByEmail returns the matching user', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');
    $repository->save($user);

    $found = $repository->findByEmail(Email::fromString('alice@example.com'));

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($user->id()))->toBeTrue();
});

test('findByEmail returns null when no user matches', function () {
    $repository = new EloquentUserRepository(new UserMapper);

    expect($repository->findByEmail(Email::fromString('missing@example.com')))->toBeNull();
});
