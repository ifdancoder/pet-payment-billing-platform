<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Membership\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Membership */
final class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id()->toString(),
            'user_id' => $this->userId()->toString(),
            'merchant_id' => $this->merchantId()->toString(),
            'role' => $this->role()->label(),
        ];
    }
}
