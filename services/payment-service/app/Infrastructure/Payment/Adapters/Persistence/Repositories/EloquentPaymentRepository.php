<?php

namespace App\Infrastructure\Payment\Adapters\Persistence\Repositories;

use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Exceptions\PaymentNotFound;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Models\PaymentModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentPaymentRepository implements IPaymentRepositoryPort
{
    public function __construct(private readonly PaymentMapper $mapper) {}

    public function save(Payment $payment): void
    {
        $model = PaymentModel::query()->with('attempts')->find($payment->id()->toString());
        $model = $this->mapper->toModel($payment, $model);
        $model->save();

        $this->persistAttempts($model, $payment);
    }

    public function get(PaymentId $id, MerchantId $merchantId): Payment
    {
        $model = PaymentModel::query()
            ->with('attempts')
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw PaymentNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function all(MerchantId $merchantId): array
    {
        return PaymentModel::query()
            ->with('attempts')
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (PaymentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function findByInvoiceId(InvoiceId $invoiceId): ?Payment
    {
        $model = PaymentModel::query()
            ->with('attempts')
            ->where('invoice_id', $invoiceId->toString())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    /**
     * Unlike an Invoice's lines, a PaymentAttempt is not immutable once
     * created — it starts Pending and later transitions to Succeeded or
     * Failed — so every attempt is an upsert, not an insert-only append.
     */
    private function persistAttempts(PaymentModel $model, Payment $payment): void
    {
        foreach ($payment->attempts() as $attempt) {
            $model->attempts()->updateOrCreate(
                ['id' => $attempt->id()->toString()],
                [
                    'provider' => $attempt->provider(),
                    'provider_reference' => $attempt->providerReference()?->toString(),
                    'status' => $attempt->status()->value,
                    'failure_code' => $attempt->failureCode(),
                    'failure_message' => $attempt->failureMessage(),
                    'started_at' => $attempt->startedAt(),
                    'completed_at' => $attempt->completedAt(),
                ],
            );
        }
    }
}
