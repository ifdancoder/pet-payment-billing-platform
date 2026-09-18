<?php

namespace App\Application\Payment\Ports\Outbound;

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeResult;

/**
 * The single seam between our business model and whatever payment
 * provider actually moves the money. Scoped to exactly what this
 * project's use cases need — not a universal abstraction meant to fit
 * every provider that might ever exist. If a second real provider shows
 * the shape is wrong, change it then.
 */
interface IPaymentGatewayPort
{
    public function charge(ChargeRequest $request): ChargeResult;
}
