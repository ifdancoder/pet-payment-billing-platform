<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Presentation\Http\V1\Resources\ProductResource;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Http\Request;

test('toArray exposes the product id, merchant, name, description and status', function () {
    $id = ProductId::generate();
    $merchantId = MerchantId::generate();
    $product = Product::create($id, $merchantId, ProductName::fromString('Pro Plan'), 'Pro tier subscription');

    $array = (new ProductResource($product))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'merchant_id' => $merchantId->toString(),
        'name' => 'Pro Plan',
        'description' => 'Pro tier subscription',
        'status' => 'active',
    ]);
});

test('constructing with a non-Product value fails with a TypeError', function () {
    new ProductResource('not-a-product');
})->throws(TypeError::class);
