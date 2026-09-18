<?php

use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantCommand;
use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantHandler;
use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Domain\Merchant\ValueObjects\MerchantStatus;

test('handle creates an Active merchant with the given name', function () {
    $merchant = app(CreateMerchantHandler::class)->handle(new CreateMerchantCommand('Acme Inc.'));

    expect($merchant->name()->toString())->toBe('Acme Inc.')
        ->and($merchant->status())->toBe(MerchantStatus::Active);
});

test('handle persists the merchant', function () {
    $merchant = app(CreateMerchantHandler::class)->handle(new CreateMerchantCommand('Acme Inc.'));

    $persisted = app(IMerchantRepositoryPort::class)->get($merchant->id());
    expect($persisted->id()->equals($merchant->id()))->toBeTrue();
});
