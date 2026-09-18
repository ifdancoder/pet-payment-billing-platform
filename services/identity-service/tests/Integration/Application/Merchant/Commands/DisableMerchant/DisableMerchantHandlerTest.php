<?php

use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantCommand;
use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantHandler;
use App\Application\Merchant\Commands\DisableMerchant\DisableMerchantCommand;
use App\Application\Merchant\Commands\DisableMerchant\DisableMerchantHandler;
use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantStatus;

test('handle disables the merchant', function () {
    $merchant = app(CreateMerchantHandler::class)->handle(new CreateMerchantCommand('Acme Inc.'));

    $disabled = app(DisableMerchantHandler::class)->handle(new DisableMerchantCommand($merchant->id()->toString()));

    expect($disabled->status())->toBe(MerchantStatus::Disabled);
});

test('handle throws MerchantNotFound when no merchant matches', function () {
    app(DisableMerchantHandler::class)->handle(new DisableMerchantCommand(MerchantId::generate()->toString()));
})->throws(MerchantNotFound::class);
