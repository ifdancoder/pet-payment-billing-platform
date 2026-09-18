<?php

use App\Domain\Merchant\Events\MerchantCreated;
use App\Domain\Merchant\Events\MerchantDisabled;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\Merchant\ValueObjects\MerchantStatus;

test('create exposes the given data and starts out Active', function () {
    $id = MerchantId::generate();
    $name = MerchantName::fromString('Acme Inc.');

    $merchant = Merchant::create($id, $name);

    expect($merchant->id()->equals($id))->toBeTrue()
        ->and($merchant->name()->equals($name))->toBeTrue()
        ->and($merchant->status())->toBe(MerchantStatus::Active);
});

test('create records a MerchantCreated event', function () {
    $id = MerchantId::generate();
    $name = MerchantName::fromString('Acme Inc.');

    $merchant = Merchant::create($id, $name);

    $events = $merchant->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(MerchantCreated::class)
        ->and($events[0]->merchantId->equals($id))->toBeTrue()
        ->and($events[0]->name->equals($name))->toBeTrue();
});

test('disable sets the status to Disabled and records a MerchantDisabled event', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    $merchant->pullRecordedEvents();

    $merchant->disable();

    expect($merchant->status())->toBe(MerchantStatus::Disabled);
    $events = $merchant->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(MerchantDisabled::class)
        ->and($events[0]->merchantId->equals($merchant->id()))->toBeTrue();
});

test('disable is idempotent when the merchant is already Disabled', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));
    $merchant->disable();
    $merchant->pullRecordedEvents();

    $merchant->disable();

    expect($merchant->status())->toBe(MerchantStatus::Disabled)
        ->and($merchant->pullRecordedEvents())->toBe([]);
});

test('pullRecordedEvents empties the recorded events', function () {
    $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString('Acme Inc.'));

    $merchant->pullRecordedEvents();

    expect($merchant->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data without recording an event', function () {
    $id = MerchantId::generate();

    $merchant = Merchant::reconstitute($id, MerchantName::fromString('Acme Inc.'), MerchantStatus::Disabled);

    expect($merchant->id()->equals($id))->toBeTrue()
        ->and($merchant->status())->toBe(MerchantStatus::Disabled)
        ->and($merchant->pullRecordedEvents())->toBe([]);
});
