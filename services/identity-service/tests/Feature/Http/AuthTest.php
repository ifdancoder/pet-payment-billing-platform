<?php

use App\Infrastructure\Auth\Adapters\Persistence\Models\RefreshTokenModel;
use Illuminate\Testing\TestResponse;

function registerAccount(): TestResponse
{
    return test()->postJson('/api/v1/auth/register', [
        'email' => 'owner@example.com',
        'password' => 'correct horse battery staple',
        'merchant_name' => 'Example Merchant',
    ]);
}

test('register atomically creates an owner account and returns a token pair', function () {
    $response = registerAccount();

    $response->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('role', 'owner')
        ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'user_id', 'merchant_id']);

    expect($response->json('refresh_token'))->toStartWith('rt_')
        ->and(RefreshTokenModel::query()->count())->toBe(1)
        ->and(RefreshTokenModel::query()->first()->token_hash)->not->toBe($response->json('refresh_token'));
});

test('login validates credentials and merchant membership', function () {
    $registered = registerAccount()->assertCreated();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'correct horse battery staple',
        'merchant_id' => $registered->json('merchant_id'),
    ])->assertOk()->assertJsonPath('role', 'owner');

    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'wrong password',
        'merchant_id' => $registered->json('merchant_id'),
    ])->assertUnauthorized();
});

test('refresh tokens rotate and reuse revokes the complete family', function () {
    $registered = registerAccount()->assertCreated();
    $original = $registered->json('refresh_token');

    $rotated = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $original])
        ->assertOk();

    expect($rotated->json('refresh_token'))->not->toBe($original)
        ->and(RefreshTokenModel::query()->whereNotNull('revoked_at')->count())->toBe(1);

    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $original])->assertUnauthorized();

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $rotated->json('refresh_token'),
    ])->assertUnauthorized();
});

test('logout revokes the refresh token family and stays idempotent', function () {
    $registered = registerAccount()->assertCreated();
    $refreshToken = $registered->json('refresh_token');

    $this->postJson('/api/v1/auth/logout', ['refresh_token' => $refreshToken])->assertNoContent();
    $this->postJson('/api/v1/auth/logout', ['refresh_token' => $refreshToken])->assertNoContent();
    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken])->assertUnauthorized();
});
