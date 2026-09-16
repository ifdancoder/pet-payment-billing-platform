<?php

use App\Application\Catalog\CatalogService;
use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogQuery;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\ProductService;
use App\Shared\Domain\ValueObjects\MerchantId;

test('getProductCatalog delegates to GetProductCatalogHandler and returns the product catalog', function () {
    $merchantId = MerchantId::generate();
    $product = app(ProductService::class)->createProduct(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $service = app(CatalogService::class);

    $catalog = $service->getProductCatalog(new GetProductCatalogQuery($merchantId->toString(), $product->id()->toString()));

    expect($catalog->product->id()->equals($product->id()))->toBeTrue()
        ->and($catalog->prices)->toBe([]);
});
