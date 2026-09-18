<?php

namespace App\Presentation\Http\V1\Resources;

use App\Application\Catalog\ReadModels\ProductCatalog;
use App\Domain\Price\Price;
use App\Presentation\Http\V1\Resources\PriceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductCatalogResource extends JsonResource
{
    public function __construct(private readonly ProductCatalog $catalog)
    {
        parent::__construct($catalog);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->catalog->product->id()->toString(),
            'merchant_id' => $this->catalog->product->merchantId()->toString(),
            'name' => $this->catalog->product->name()->toString(),
            'description' => $this->catalog->product->description(),
            'status' => $this->catalog->product->status()->label(),
            'prices' => array_map(
                fn (Price $price) => (new PriceResource($price))->toArray($request),
                $this->catalog->prices,
            ),
        ];
    }
}
