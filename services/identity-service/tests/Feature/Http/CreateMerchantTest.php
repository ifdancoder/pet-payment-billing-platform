<?php

use App\Infrastructure\Merchant\Adapters\Persistence\Models\MerchantModel;

test('a request creates a merchant', function () {
    $response = $this->postJson('/api/v1/merchants', ['name' => 'Acme Inc.']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Acme Inc.')
        ->assertJsonPath('data.status', 'active');
    expect(MerchantModel::query()->where('name', 'Acme Inc.')->exists())->toBeTrue();
});

test('a request without a name is rejected', function () {
    $response = $this->postJson('/api/v1/merchants', []);

    $response->assertUnprocessable()->assertJsonValidationErrors('name');
});
