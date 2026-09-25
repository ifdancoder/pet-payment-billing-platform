<?php

test('a protected endpoint rejects a missing or invalid access token', function () {
    $this->withToken('not-a-token')
        ->getJson(customerApi())
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer');
});

test('an access token cannot cross merchant boundaries', function () {
    $otherMerchant = '22222222-2222-4222-8222-222222222222';

    $this->authenticateAs($otherMerchant)
        ->getJson(customerApi())
        ->assertForbidden();
});

test('a viewer can read customers but cannot create them', function () {
    $this->authenticateAs(aMerchantId()->toString(), 'viewer')
        ->getJson(customerApi())
        ->assertOk();

    $this->postJson(customerApi(), ['email' => 'viewer@example.com', 'name' => 'Viewer'])
        ->assertForbidden();
});

test('api responses preserve a valid correlation id and replace an invalid one', function () {
    $correlationId = '11111111-1111-4111-8111-111111111111';
    $this->withHeader('X-Correlation-ID', strtoupper($correlationId))
        ->getJson(customerApi())
        ->assertHeader('X-Correlation-ID', $correlationId);

    $generated = $this->withHeader('X-Correlation-ID', 'not-a-uuid')->getJson(customerApi());
    expect($generated->headers->get('X-Correlation-ID'))->toMatch('/^[0-9a-f-]{36}$/');
});
