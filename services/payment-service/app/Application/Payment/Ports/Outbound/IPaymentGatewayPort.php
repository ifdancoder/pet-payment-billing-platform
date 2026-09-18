<?php

namespace App\Application\Payment\Ports\Outbound;

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeResult;

interface IPaymentGatewayPort
{
    public function charge(ChargeRequest $request): ChargeResult;
}
