<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Payment\Payment;
use App\Domain\Payment\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentResource extends JsonResource
{
    public function __construct(private readonly Payment $payment)
    {
        parent::__construct($payment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->payment->id()->toString(),
            'invoice_id' => $this->payment->invoiceId()->toString(),
            'merchant_id' => $this->payment->merchantId()->toString(),
            'customer_id' => $this->payment->customerId()->toString(),
            'amount_minor_units' => $this->payment->money()->amountMinorUnits(),
            'currency' => $this->payment->money()->currency()->value,
            'status' => $this->payment->status()->label(),
            'paid_at' => $this->payment->paidAt()?->format(DATE_ATOM),
            'failed_at' => $this->payment->failedAt()?->format(DATE_ATOM),
            'attempts' => array_map($this->attemptToArray(...), $this->payment->attempts()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptToArray(PaymentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id()->toString(),
            'provider' => $attempt->provider(),
            'provider_reference' => $attempt->providerReference()?->toString(),
            'status' => $attempt->status()->label(),
            'failure_code' => $attempt->failureCode(),
            'failure_message' => $attempt->failureMessage(),
            'started_at' => $attempt->startedAt()->format(DATE_ATOM),
            'completed_at' => $attempt->completedAt()?->format(DATE_ATOM),
        ];
    }
}
