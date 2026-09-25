<?php

use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Integration');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something() {}

function aMerchantId(): MerchantId
{
    return MerchantId::fromString('11111111-1111-4111-8111-111111111111');
}

function customerApi(string $suffix = ''): string
{
    return '/api/v1/merchants/'.aMerchantId()->toString().'/customers'.$suffix;
}
