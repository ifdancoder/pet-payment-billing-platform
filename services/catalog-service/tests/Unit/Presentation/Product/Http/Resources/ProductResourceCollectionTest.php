<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductResourceCollection;
use Illuminate\Http\Request;

test('toArray wraps every product through ProductResource', function () {
    $pro = Product::create(ProductId::generate(), ProductName::fromString('Pro Plan'));
    $team = Product::create(ProductId::generate(), ProductName::fromString('Team Plan'));

    $array = (new ProductResourceCollection([$pro, $team]))->toArray(new Request);

    expect($array)->toHaveCount(2)
        ->and($array[0])->toBe(['id' => $pro->id()->toString(), 'name' => 'Pro Plan', 'status' => 'active'])
        ->and($array[1])->toBe(['id' => $team->id()->toString(), 'name' => 'Team Plan', 'status' => 'active']);
});

test('constructing with a non-array value fails with a TypeError', function () {
    new ProductResourceCollection('not-an-array');
})->throws(TypeError::class);

test('constructing with an array containing a non-Product value fails with a TypeError', function () {
    new ProductResourceCollection(['not-a-product']);
})->throws(TypeError::class);
