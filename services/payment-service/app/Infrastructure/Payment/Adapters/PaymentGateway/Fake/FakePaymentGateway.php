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
    /**
     * ChargeRequest carries only an attempt id and an amount — there's
     * no card/token field to simulate a decline with, and adding one
     * would mean threading a "how should this fail" concept through
     * Subscription and Billing too, just to reach a test-only gateway.
     * The amount itself is already the one signal that travels
     * unmodified from an E2E test's own `POST .../prices` call all the
     * way down to here, so it doubles as the trigger: a charge for
     * exactly this amount, in any currency, always declines. Chosen to
     * be unmistakably deliberate — no real price is ever going to land
     * on it by accident. See tests/e2e/failed-payment/.
     */
    public const int DECLINE_TRIGGER_AMOUNT_MINOR_UNITS = 66660000;

    public function charge(ChargeRequest $request): ChargeResult
    {
        if ($request->money->amountMinorUnits() === self::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS) {
            return ChargeResult::failed('card_declined');
        }

        return ChargeResult::succeeded("fake:{$request->attemptId->toString()}");
    }
}
