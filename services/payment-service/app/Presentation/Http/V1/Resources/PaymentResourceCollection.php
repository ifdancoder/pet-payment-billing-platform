<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Payment\Payment;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class PaymentResourceCollection extends ResourceCollection
{
    public $collects = PaymentResource::class;

    /**
     * @param  array<int, Payment>  $payments
     */
    public function __construct(array $payments)
    {
        parent::__construct($payments);
    }
}
