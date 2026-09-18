<?php

use App\Infrastructure\User\Adapters\Persistence\Models\UserModel;

test('a request registers a user', function () {
    $response = $this->postJson('/api/v1/users', ['email' => 'alice@example.com', 'password' => 'correct-horse-battery-staple']);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'alice@example.com')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.password_hash');
    expect(UserModel::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});

test('a request with an already-registered email is rejected', function () {
    $this->postJson('/api/v1/users', ['email' => 'alice@example.com', 'password' => 'correct-horse-battery-staple']);

    $response = $this->postJson('/api/v1/users', ['email' => 'alice@example.com', 'password' => 'a-different-password']);

    $response->assertStatus(409);
});

test('a request with an invalid email is rejected', function () {
    $response = $this->postJson('/api/v1/users', ['email' => 'not-an-email', 'password' => 'correct-horse-battery-staple']);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('a request with too short a password is rejected', function () {
    $response = $this->postJson('/api/v1/users', ['email' => 'alice@example.com', 'password' => 'short']);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});
