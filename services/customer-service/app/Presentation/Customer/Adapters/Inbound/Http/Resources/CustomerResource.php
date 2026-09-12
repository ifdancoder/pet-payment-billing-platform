<?php

namespace App\Presentation\Customer\Adapters\Inbound\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->toString(),
            'email' => $this->resource->email()->toString(),
            'name' => $this->resource->name()->toString(),
        ];
    }
}
