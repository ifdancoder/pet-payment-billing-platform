<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Product\Product;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ProductResourceCollection extends ResourceCollection
{
    public $collects = ProductResource::class;

    /**
     * @param  array<int, Product>  $products
     */
    public function __construct(array $products)
    {
        parent::__construct($products);
    }
}
