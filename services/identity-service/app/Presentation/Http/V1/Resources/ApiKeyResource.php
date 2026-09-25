<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\ApiKey\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApiKey */
final class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id(), 'merchant_id' => $this->merchantId(), 'name' => $this->name(),
            'role' => $this->role(), 'scopes' => $this->scopes(),
            'revoked_at' => $this->revokedAt()?->format(DATE_ATOM),
            'last_used_at' => $this->lastUsedAt()?->format(DATE_ATOM),
        ];
    }
}
