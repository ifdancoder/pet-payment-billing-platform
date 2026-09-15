<?php

use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;

test('save persists a new product', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $product = Product::create(ProductId::generate(), ProductName::fromString('Pro Plan'));

    $repository->save($product);

    expect(ProductModel::query()->where('id', $product->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted product instead of duplicating it', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $id = ProductId::generate();
    $repository->save(Product::create($id, ProductName::fromString('Old Name')));

    $repository->save(Product::reconstitute($id, ProductName::fromString('New Name')));

    expect(ProductModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(ProductModel::query()->find($id->toString())->name)->toBe('New Name');
});

test('get returns the matching product', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $id = ProductId::generate();
    $repository->save(Product::create($id, ProductName::fromString('Pro Plan')));

    $found = $repository->get($id);

    expect($found->id()->equals($id))->toBeTrue()
        ->and($found->name()->toString())->toBe('Pro Plan');
});

test('get throws ProductNotFound when no product matches', function () {
    $repository = new EloquentProductRepository(new ProductMapper);

    $repository->get(ProductId::generate());
})->throws(ProductNotFound::class);

test('all returns every persisted product', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $repository->save(Product::create(ProductId::generate(), ProductName::fromString('Pro Plan')));
    $repository->save(Product::create(ProductId::generate(), ProductName::fromString('Team Plan')));

    $products = $repository->all();

    expect($products)->toHaveCount(2)
        ->and(array_map(fn (Product $p) => $p->name()->toString(), $products))
        ->toEqualCanonicalizing(['Pro Plan', 'Team Plan']);
});

test('all returns an empty array when there are no products', function () {
    $repository = new EloquentProductRepository(new ProductMapper);

    expect($repository->all())->toBe([]);
});
