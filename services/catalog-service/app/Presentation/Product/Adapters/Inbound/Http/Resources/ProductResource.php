<?php

namespace App\Presentation\Product\Adapters\Inbound\Http\Resources;

use App\Domain\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function __construct(private readonly Product $product)
    {
        parent::__construct($product);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->product->id()->toString(),
            'name' => $this->product->name()->toString(),
        ];
    }
}
