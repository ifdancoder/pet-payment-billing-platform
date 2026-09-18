<?php

use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\Merchant\ValueObjects\MerchantStatus;
use App\Infrastructure\Merchant\Adapters\Persistence\Mappers\MerchantMapper;
use App\Infrastructure\Merchant\Adapters\Persistence\Models\MerchantModel;
use App\Infrastructure\Merchant\Adapters\Persistence\Repositories\EloquentMerchantRepository;

test('save persists a new merchant', function () {
    $repository = new EloquentMerchantRepository(new MerchantMapper);
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));

    $repository->save($merchant);

    expect(MerchantModel::query()->where('id', $merchant->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted merchant instead of duplicating it', function () {
    $repository = new EloquentMerchantRepository(new MerchantMapper);
    $id = MerchantId::generate();
    $repository->save(Merchant::create($id, MerchantName::fromString('Old Name')));

    $repository->save(Merchant::reconstitute($id, MerchantName::fromString('New Name'), MerchantStatus::Active));

    expect(MerchantModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(MerchantModel::query()->find($id->toString())->name)->toBe('New Name');
});

test('get returns the matching merchant', function () {
    $repository = new EloquentMerchantRepository(new MerchantMapper);
    $id = MerchantId::generate();
    $repository->save(Merchant::create($id, MerchantName::fromString('Acme Inc.')));

    $found = $repository->get($id);

    expect($found->id()->equals($id))->toBeTrue()
        ->and($found->name()->toString())->toBe('Acme Inc.');
});

test('get throws MerchantNotFound when no merchant matches', function () {
    $repository = new EloquentMerchantRepository(new MerchantMapper);

    $repository->get(MerchantId::generate());
})->throws(MerchantNotFound::class);
