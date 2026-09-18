<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function __construct(private readonly Invoice $invoice)
    {
        parent::__construct($invoice);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->invoice->id()->toString(),
            'merchant_id' => $this->invoice->merchantId()->toString(),
            'customer_id' => $this->invoice->customerId()->toString(),
            'subscription_id' => $this->invoice->subscriptionId()->toString(),
            'period_start' => $this->invoice->period()->start()->format(DATE_ATOM),
            'period_end' => $this->invoice->period()->end()->format(DATE_ATOM),
            'currency' => $this->invoice->total()->currency()->value,
            'subtotal_amount_minor_units' => $this->invoice->subtotal()->amountMinorUnits(),
            'total_amount_minor_units' => $this->invoice->total()->amountMinorUnits(),
            'status' => $this->invoice->status()->label(),
            'payment_id' => $this->invoice->paymentId()?->toString(),
            'paid_at' => $this->invoice->paidAt()?->format(DATE_ATOM),
            'voided_at' => $this->invoice->voidedAt()?->format(DATE_ATOM),
            'lines' => array_map($this->lineToArray(...), $this->invoice->lines()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineToArray(InvoiceLine $line): array
    {
        return [
            'id' => $line->id()->toString(),
            'product_id' => $line->productId()?->toString(),
            'price_id' => $line->priceId()?->toString(),
            'description' => $line->description(),
            'quantity' => $line->quantity(),
            'unit_amount_minor_units' => $line->unitAmount()->amountMinorUnits(),
            'total_amount_minor_units' => $line->total()->amountMinorUnits(),
        ];
    }
}
