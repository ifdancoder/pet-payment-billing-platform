<?php

namespace App\Presentation\Product\Adapters\Inbound\Http\Controllers;

use App\Application\Catalog\Ports\Inbound\ICatalogServicePort;
use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogQuery;
use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\UpdateProduct\UpdateProductCommand;
use App\Application\Product\Ports\Inbound\IProductServicePort;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Presentation\Product\Adapters\Inbound\Http\Requests\CreateProductRequest;
use App\Presentation\Product\Adapters\Inbound\Http\Requests\UpdateProductRequest;
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductCatalogResource;
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductResource;
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function __construct(
        private readonly IProductServicePort $productService,
        private readonly ICatalogServicePort $catalogService,
    ) {}

    public function index(): JsonResponse
    {
        $products = $this->productService->listProducts(new ListProductsQuery);

        return (new ProductResourceCollection($products))->response();
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct(new CreateProductCommand(
            $request->validated('name'),
        ));

        return ProductResource::make($product)->response()->setStatusCode(201);
    }

    public function show(string $product): JsonResponse
    {
        $catalog = $this->catalogService->getProductCatalog(new GetProductCatalogQuery($product));

        return (new ProductCatalogResource($catalog))->response();
    }

    public function update(UpdateProductRequest $request, string $product): JsonResponse
    {
        $updated = $this->productService->updateProduct(new UpdateProductCommand(
            $product,
            $request->validated('name'),
        ));

        return ProductResource::make($updated)->response();
    }

    public function archive(string $product): JsonResponse
    {
        $archived = $this->productService->archiveProduct(new ArchiveProductCommand($product));

        return ProductResource::make($archived)->response();
    }
}
