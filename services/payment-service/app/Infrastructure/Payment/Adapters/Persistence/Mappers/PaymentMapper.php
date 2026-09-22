<?php

namespace App\Infrastructure\Payment\Adapters\Persistence\Mappers;

use App\Domain\Payment\Payment;
use App\Domain\Payment\PaymentAttempt;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentAttemptStatus;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Infrastructure\Payment\Adapters\Persistence\Models\PaymentAttemptModel;
use App\Infrastructure\Payment\Adapters\Persistence\Models\PaymentModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class PaymentMapper
{
    public function toDomain(PaymentModel $model): Payment
    {
        $attempts = $model->attempts
            ->map(fn (PaymentAttemptModel $attempt) => PaymentAttempt::reconstitute(
                PaymentAttemptId::fromString($attempt->id),
                $attempt->provider,
                $attempt->provider_reference === null ? null : ProviderReference::of($attempt->provider_reference),
                PaymentAttemptStatus::from($attempt->status),
                $attempt->failure_code,
                $attempt->failure_message,
                $attempt->started_at->toDateTimeImmutable(),
                $attempt->completed_at?->toDateTimeImmutable(),
            ))
            ->all();

        return Payment::reconstitute(
            PaymentId::fromString($model->id),
            InvoiceId::fromString($model->invoice_id),
            MerchantId::fromString($model->merchant_id),
            CustomerId::fromString($model->customer_id),
            Money::of($model->amount_minor_units, Currency::from($model->currency)),
            PaymentStatus::from($model->status),
            $attempts,
            $model->paid_at?->toDateTimeImmutable(),
            $model->failed_at?->toDateTimeImmutable(),
            $model->billing_reason,
        );
    }

    public function toModel(Payment $payment, ?PaymentModel $model = null): PaymentModel
    {
        $model ??= new PaymentModel;

        $model->id = $payment->id()->toString();
        $model->invoice_id = $payment->invoiceId()->toString();
        $model->merchant_id = $payment->merchantId()->toString();
        $model->customer_id = $payment->customerId()->toString();
        $model->amount_minor_units = $payment->money()->amountMinorUnits();
        $model->currency = $payment->money()->currency()->value;
        $model->billing_reason = $payment->billingReason();
        $model->status = $payment->status()->value;
        $model->paid_at = $payment->paidAt();
        $model->failed_at = $payment->failedAt();

        return $model;
    }
}
