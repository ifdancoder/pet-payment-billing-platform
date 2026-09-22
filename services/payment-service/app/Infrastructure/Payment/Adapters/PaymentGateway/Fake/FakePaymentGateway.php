<?php

namespace App\Infrastructure\Payment\Adapters\PaymentGateway\Fake;

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeResult;
use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;

final class FakePaymentGateway implements IPaymentGatewayPort
{
    /**
     * The API has no payment instrument that tests can use to request a decline.
     * This reserved amount provides a deterministic end-to-end failure trigger.
     */
    public const int DECLINE_TRIGGER_AMOUNT_MINOR_UNITS = 66660000;

    /**
     * This amount succeeds initially and declines renewal invoices.
     */
    public const int DECLINE_RENEWAL_TRIGGER_AMOUNT_MINOR_UNITS = 77770000;

    public function charge(ChargeRequest $request): ChargeResult
    {
        if ($request->money->amountMinorUnits() === self::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS
            || ($request->money->amountMinorUnits() === self::DECLINE_RENEWAL_TRIGGER_AMOUNT_MINOR_UNITS
                && $request->billingReason === 'subscription_cycle')) {
            return ChargeResult::failed('card_declined');
        }

        return ChargeResult::succeeded("fake:{$request->attemptId->toString()}");
    }
}
