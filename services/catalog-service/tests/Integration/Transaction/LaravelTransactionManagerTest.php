<?php

use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;
use App\Shared\Domain\ValueObjects\MerchantId;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('run returns the callback result', function () {
    $manager = new LaravelTransactionManager(DB::connection());

    $result = $manager->run(fn () => 'ok');

    expect($result)->toBe('ok');
});

test('run commits every write performed inside the callback', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    $manager->run(function () use ($id) {
        ProductModel::query()->create(['id' => $id, 'merchant_id' => MerchantId::generate()->toString(), 'name' => 'Pro Plan', 'status' => 1]);
    });

    expect(ProductModel::query()->where('id', $id)->exists())->toBeTrue();
});

test('run rolls back every write performed inside the callback when it throws', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    try {
        $manager->run(function () use ($id) {
            ProductModel::query()->create(['id' => $id, 'merchant_id' => MerchantId::generate()->toString(), 'name' => 'Pro Plan', 'status' => 1]);

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(ProductModel::query()->where('id', $id)->exists())->toBeFalse();
});
