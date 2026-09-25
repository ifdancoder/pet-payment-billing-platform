<?php

use App\Infrastructure\ApiKey\Adapters\Persistence\Models\ApiKeyModel;
use App\Infrastructure\Membership\Adapters\Persistence\Models\MembershipModel;

function ownerAccount(): array
{
    return test()->postJson('/api/v1/auth/register', [
        'email' => 'iam-owner@example.com',
        'password' => 'correct horse battery staple',
        'merchant_name' => 'IAM Merchant',
    ])->assertCreated()->json();
}

test('owner manages merchant memberships and cross-tenant access is rejected', function () {
    $owner = ownerAccount();
    $user = $this->withToken($owner['access_token'])->postJson('/api/v1/users', [
        'email' => 'member@example.com', 'password' => 'correct horse battery staple',
    ])->assertCreated()->json('data');

    $membership = $this->withToken($owner['access_token'])
        ->postJson("/api/v1/merchants/{$owner['merchant_id']}/memberships", ['user_id' => $user['id'], 'role' => 'viewer'])
        ->assertCreated()->assertJsonPath('data.role', 'viewer')->json('data');

    $this->getJson("/api/v1/merchants/{$owner['merchant_id']}/memberships")
        ->assertOk()->assertJsonCount(2, 'data');
    $this->patchJson("/api/v1/merchants/{$owner['merchant_id']}/memberships/{$membership['id']}", ['role' => 'finance'])
        ->assertOk()->assertJsonPath('data.role', 'finance');
    $this->getJson('/api/v1/merchants/00000000-0000-4000-8000-000000000099/memberships')->assertForbidden();
    $this->deleteJson("/api/v1/merchants/{$owner['merchant_id']}/memberships/{$membership['id']}")->assertNoContent();
    expect(MembershipModel::query()->whereKey($membership['id'])->exists())->toBeFalse();
});

test('api key secret is returned once, stored hashed, exchangeable, and revocable', function () {
    $owner = ownerAccount();
    $created = $this->withToken($owner['access_token'])
        ->postJson("/api/v1/merchants/{$owner['merchant_id']}/api-keys", [
            'name' => 'CI deploy', 'role' => 'developer', 'scopes' => ['catalog:write'],
        ])->assertCreated()->json('data');

    expect($created['secret'])->toStartWith('pk_')
        ->and(ApiKeyModel::query()->findOrFail($created['id'])->secret_hash)->not->toBe($created['secret']);

    $exchanged = $this->postJson('/api/v1/auth/api-key', ['api_key' => $created['secret']])
        ->assertOk()->assertJsonPath('role', 'developer')->assertJsonPath('scopes.0', 'catalog:write');
    expect($exchanged->json('access_token'))->toBeString();

    $this->withToken($owner['access_token'])
        ->deleteJson("/api/v1/merchants/{$owner['merchant_id']}/api-keys/{$created['id']}")->assertNoContent();
    $this->postJson('/api/v1/auth/api-key', ['api_key' => $created['secret']])->assertUnauthorized();
});

test('legacy identity writes require authentication', function () {
    $this->withoutHeader('Authorization')->postJson('/api/v1/users', [
        'email' => 'public@example.com', 'password' => 'correct horse battery staple',
    ])->assertUnauthorized();
    $this->withoutHeader('Authorization')->postJson('/api/v1/merchants', ['name' => 'Public Merchant'])->assertUnauthorized();
});

test('the last owner cannot be removed or demoted', function () {
    $owner = ownerAccount();
    $membership = MembershipModel::query()->where('merchant_id', $owner['merchant_id'])->firstOrFail();

    $this->withToken($owner['access_token'])
        ->patchJson("/api/v1/merchants/{$owner['merchant_id']}/memberships/{$membership->id}", ['role' => 'admin'])
        ->assertConflict();
    $this->deleteJson("/api/v1/merchants/{$owner['merchant_id']}/memberships/{$membership->id}")
        ->assertConflict();
});
