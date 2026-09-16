<?php

use App\Infrastructure\Subscription\Adapters\Persistence\Models\SubscriptionModel;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeSubscriptionRow(string $id): array
{
    return [
        'id' => $id,
        'merchant_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'price_id' => (string) Str::uuid(),
        'product_id' => (string) Str::uuid(),
        'price_amount_minor_units' => 1999,
        'price_currency' => 'USD',
        'billing_interval' => 3,
        'billing_interval_count' => 1,
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
        SubscriptionModel::query()->create(makeSubscriptionRow($id));
    });

    expect(SubscriptionModel::query()->where('id', $id)->exists())->toBeTrue();
});

test('run rolls back every write performed inside the callback when it throws', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    try {
        $manager->run(function () use ($id) {
            SubscriptionModel::query()->create(makeSubscriptionRow($id));

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(SubscriptionModel::query()->where('id', $id)->exists())->toBeFalse();
});
