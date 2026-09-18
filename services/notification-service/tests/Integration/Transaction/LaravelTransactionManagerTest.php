<?php

use App\Infrastructure\Notification\Adapters\Persistence\Models\NotificationModel;
use App\Shared\Domain\ValueObjects\MerchantId;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeNotificationRow(string $id): array
{
    return [
        'id' => $id,
        'merchant_id' => MerchantId::generate()->toString(),
        'source_event_id' => 'evt_payment_succeeded_1',
        'type' => 1,
        'channel' => 1,
        'recipient' => 'customer@example.com',
        'subject' => 'Your receipt',
        'body_text' => 'Thanks for your payment.',
        'body_html' => '<p>Thanks for your payment.</p>',
        'status' => 1,
        'deduplication_key' => 'payment_receipt:'.$id.':customer@example.com',
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
        NotificationModel::query()->create(makeNotificationRow($id));
    });

    expect(NotificationModel::query()->where('id', $id)->exists())->toBeTrue();
});

test('run rolls back every write performed inside the callback when it throws', function () {
    $manager = new LaravelTransactionManager(DB::connection());
    $id = (string) Str::uuid();

    try {
        $manager->run(function () use ($id) {
            NotificationModel::query()->create(makeNotificationRow($id));

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(NotificationModel::query()->where('id', $id)->exists())->toBeFalse();
});
