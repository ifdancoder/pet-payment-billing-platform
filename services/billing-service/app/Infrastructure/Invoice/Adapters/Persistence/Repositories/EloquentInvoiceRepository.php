<?php

namespace App\Infrastructure\Invoice\Adapters\Persistence\Repositories;

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Exceptions\InvoiceNotFound;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Models\InvoiceModel;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class EloquentInvoiceRepository implements IInvoiceRepositoryPort
{
    public function __construct(private readonly InvoiceMapper $mapper) {}

    public function save(Invoice $invoice): void
    {
        $model = InvoiceModel::query()->with('lines')->find($invoice->id()->toString());
        $model = $this->mapper->toModel($invoice, $model);
        $model->save();

        $this->persistNewLines($model, $invoice);
    }

    public function get(InvoiceId $id, MerchantId $merchantId): Invoice
    {
        $model = InvoiceModel::query()
            ->with('lines')
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw InvoiceNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function all(MerchantId $merchantId): array
    {
        return InvoiceModel::query()
            ->with('lines')
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (InvoiceModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function findByBillingCycle(SubscriptionId $subscriptionId, DateTimeImmutable $periodStart): ?Invoice
    {
        $model = InvoiceModel::query()
            ->with('lines')
            ->where('subscription_id', $subscriptionId->toString())
            ->where('period_start', $periodStart)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    /**
     * Invoice lines are immutable once created (Invoice::create() is the
     * only way any exist), so saving only ever needs to insert lines that
     * aren't in the database yet — never update or delete one.
     */
    private function persistNewLines(InvoiceModel $model, Invoice $invoice): void
    {
        $existingLineIds = $model->lines->pluck('id')->all();

        foreach ($invoice->lines() as $line) {
            if (in_array($line->id()->toString(), $existingLineIds, true)) {
                continue;
            }

            $model->lines()->create([
                'id' => $line->id()->toString(),
                'product_id' => $line->productId()?->toString(),
                'price_id' => $line->priceId()?->toString(),
                'description' => $line->description(),
                'quantity' => $line->quantity(),
                'unit_amount_minor_units' => $line->unitAmount()->amountMinorUnits(),
                'total_amount_minor_units' => $line->total()->amountMinorUnits(),
            ]);
        }
    }
}
