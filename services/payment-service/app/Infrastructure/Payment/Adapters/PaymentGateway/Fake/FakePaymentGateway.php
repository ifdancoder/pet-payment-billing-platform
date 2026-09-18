<?php

namespace App\Infrastructure\Payment\Adapters\PaymentGateway\Fake;

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeResult;
use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;

/**
 * A deterministic, no-network stand-in for a real provider (Stripe).
 * Bound in local/testing environments so the whole Create -> Process ->
 * Succeeded flow can be exercised end to end without real money or an
 * external dependency.
 */
final class FakePaymentGateway implements IPaymentGatewayPort
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        return ChargeResult::succeeded("fake:{$request->attemptId->toString()}");
    }
}
