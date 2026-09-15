<?php

namespace App\Presentation\Customer\Adapters\Inbound\Http\Resources;

use App\Domain\Customer\Customer;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class CustomerResourceCollection extends ResourceCollection
{
    public $collects = CustomerResource::class;

    /**
     * @param  array<int, Customer>  $customers
     */
    public function __construct(array $customers)
    {
        parent::__construct($customers);
    }
}
