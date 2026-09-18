<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Presentation\Http\V1\Resources\ProductResourceCollection;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Http\Request;

test('toArray wraps every product through ProductResource', function () {
    $merchantId = MerchantId::generate();
    $pro = Product::create(ProductId::generate(), $merchantId, ProductName::fromString('Pro Plan'));
    $team = Product::create(ProductId::generate(), $merchantId, ProductName::fromString('Team Plan'));

    $array = (new ProductResourceCollection([$pro, $team]))->toArray(new Request);

    expect($array)->toHaveCount(2)
        ->and($array[0])->toBe([
            'id' => $pro->id()->toString(),
            'merchant_id' => $merchantId->toString(),
            'name' => 'Pro Plan',
            'description' => null,
            'status' => 'active',
        ])
        ->and($array[1])->toBe([
            'id' => $team->id()->toString(),
            'merchant_id' => $merchantId->toString(),
            'name' => 'Team Plan',
            'description' => null,
            'status' => 'active',
        ]);
});

test('constructing with a non-array value fails with a TypeError', function () {
    new ProductResourceCollection('not-an-array');
})->throws(TypeError::class);

test('constructing with an array containing a non-Product value fails with a TypeError', function () {
    new ProductResourceCollection(['not-a-product']);
})->throws(TypeError::class);
