<?php

namespace App\Infrastructure\Invoice\Adapters\Persistence\Mappers;

use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\PriceId;
use App\Domain\Invoice\ValueObjects\ProductId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Models\InvoiceLineModel;
use App\Infrastructure\Invoice\Adapters\Persistence\Models\InvoiceModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class InvoiceMapper
{
    public function toDomain(InvoiceModel $model): Invoice
    {
        $currency = Currency::from($model->currency);

        $lines = $model->lines
            ->map(fn (InvoiceLineModel $line) => InvoiceLine::create(
                InvoiceLineId::fromString($line->id),
                $line->product_id === null ? null : ProductId::fromString($line->product_id),
                $line->price_id === null ? null : PriceId::fromString($line->price_id),
                $line->description,
                Money::of($line->unit_amount_minor_units, $currency),
                $line->quantity,
            ))
            ->all();

        return Invoice::reconstitute(
            InvoiceId::fromString($model->id),
            MerchantId::fromString($model->merchant_id),
            CustomerId::fromString($model->customer_id),
            SubscriptionId::fromString($model->subscription_id),
            BillingPeriod::of($model->period_start->toDateTimeImmutable(), $model->period_end->toDateTimeImmutable()),
            $lines,
            Money::of($model->subtotal_amount_minor_units, $currency),
            Money::of($model->total_amount_minor_units, $currency),
            InvoiceStatus::from($model->status),
            $model->payment_id === null ? null : PaymentId::fromString($model->payment_id),
            $model->paid_at?->toDateTimeImmutable(),
            $model->voided_at?->toDateTimeImmutable(),
        );
    }

    public function toModel(Invoice $invoice, ?InvoiceModel $model = null): InvoiceModel
    {
        $model ??= new InvoiceModel;

        $model->id = $invoice->id()->toString();
        $model->merchant_id = $invoice->merchantId()->toString();
        $model->customer_id = $invoice->customerId()->toString();
        $model->subscription_id = $invoice->subscriptionId()->toString();
        $model->period_start = $invoice->period()->start();
        $model->period_end = $invoice->period()->end();
        $model->currency = $invoice->total()->currency()->value;
        $model->subtotal_amount_minor_units = $invoice->subtotal()->amountMinorUnits();
        $model->total_amount_minor_units = $invoice->total()->amountMinorUnits();
        $model->status = $invoice->status()->value;
        $model->payment_id = $invoice->paymentId()?->toString();
        $model->paid_at = $invoice->paidAt();
        $model->voided_at = $invoice->voidedAt();

        return $model;
    }
}
