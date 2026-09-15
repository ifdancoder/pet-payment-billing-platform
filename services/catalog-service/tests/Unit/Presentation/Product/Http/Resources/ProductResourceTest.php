<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductResource;
use Illuminate\Http\Request;

test('toArray exposes the product id and name', function () {
    $id = ProductId::generate();
    $product = Product::create($id, ProductName::fromString('Pro Plan'));

    $array = (new ProductResource($product))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'name' => 'Pro Plan',
    ]);
});

test('constructing with a non-Product value fails with a TypeError', function () {
    new ProductResource('not-a-product');
})->throws(TypeError::class);
