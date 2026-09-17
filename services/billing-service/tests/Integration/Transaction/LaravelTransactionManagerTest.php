<?php

use App\Infrastructure\Invoice\Adapters\Persistence\Models\InvoiceModel;
use App\Shared\Domain\ValueObjects\MerchantId;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeInvoiceRow(string $id): array
{
    $now = now();

    return [
        'id' => $id,
        'merchant_id' => MerchantId::generate()->toString(),
        'customer_id' => (string) Str::uuid(),
        'subscription_id' => (string) Str::uuid(),
        'period_start' => $now,
        'period_end' => $now->copy()->addMonth(),
        'currency' => 'USD',
        'subtotal_amount_minor_units' => 1999,
        'total_amount_minor_units' => 1999,
        'status' => 1,
    ];
}

test('run returns the callback result', function () {
    $manager = new LaravelTransactionManager(DB::connection());

    $result = $manager->run(fn () => 'ok');

    expect($result)->toBe('ok');
});

test('run commits every write performed inside the callback', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    $manager->run(function () use ($id) {
        InvoiceModel::query()->create(makeInvoiceRow($id));
    });

    expect(InvoiceModel::query()->where('id', $id)->exists())->toBeTrue();
});

test('run rolls back every write performed inside the callback when it throws', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    try {
        $manager->run(function () use ($id) {
            InvoiceModel::query()->create(makeInvoiceRow($id));

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(InvoiceModel::query()->where('id', $id)->exists())->toBeFalse();
});
