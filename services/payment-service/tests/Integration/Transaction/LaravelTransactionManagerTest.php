<?php

use App\Infrastructure\Payment\Adapters\Persistence\Models\PaymentModel;
use App\Shared\Domain\ValueObjects\MerchantId;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makePaymentRow(string $id): array
{
    return [
        'id' => $id,
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => MerchantId::generate()->toString(),
        'customer_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
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
        PaymentModel::query()->create(makePaymentRow($id));
    });

    expect(PaymentModel::query()->where('id', $id)->exists())->toBeTrue();
});

test('run rolls back every write performed inside the callback when it throws', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    try {
        $manager->run(function () use ($id) {
            PaymentModel::query()->create(makePaymentRow($id));

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(PaymentModel::query()->where('id', $id)->exists())->toBeFalse();
});
