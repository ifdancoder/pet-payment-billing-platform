<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Customer\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerResource extends JsonResource
{
    public function __construct(private readonly Customer $customer)
    {
        parent::__construct($customer);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->customer->id()->toString(),
            'email' => $this->customer->email()->toString(),
            'name' => $this->customer->name()->toString(),
        ];
    }
}
