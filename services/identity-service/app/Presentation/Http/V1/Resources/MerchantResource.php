<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Merchant\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MerchantResource extends JsonResource
{
    public function __construct(private readonly Merchant $merchant)
    {
        parent::__construct($merchant);
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->merchant->id()->toString(),
            'name' => $this->merchant->name()->toString(),
            'status' => $this->merchant->status()->label(),
        ];
    }
}
