<?php

use Platform\Auth\Ed25519AccessTokenCodec;
use Platform\Auth\InvalidAccessToken;

function tokenCodec(): Ed25519AccessTokenCodec
{
    return new Ed25519AccessTokenCodec(
        'issuer',
        'audience',
        (string) getenv('AUTH_ED25519_PUBLIC_KEY_BASE64'),
        (string) getenv('AUTH_ED25519_SECRET_KEY_BASE64'),
        900,
        0,
    );
}

test('it issues and verifies an Ed25519 access token', function () {
    $token = tokenCodec()->issue('user-id', 'merchant-id', 'owner', 'token-id', 1_000);
    $claims = tokenCodec()->verify($token, 1_001);

    expect($claims->subject)->toBe('user-id')
        ->and($claims->merchantId)->toBe('merchant-id')
        ->and($claims->role)->toBe('owner')
        ->and($claims->expiresAt)->toBe(1_900);
});

test('it rejects a modified token', function () {
    $token = tokenCodec()->issue('user-id', 'merchant-id', 'owner', 'token-id', 1_000);
    $token[20] = $token[20] === 'a' ? 'b' : 'a';

    tokenCodec()->verify($token, 1_001);
})->throws(InvalidAccessToken::class);

test('it rejects an expired token', function () {
    $token = tokenCodec()->issue('user-id', 'merchant-id', 'owner', 'token-id', 1_000);

    tokenCodec()->verify($token, 1_900);
})->throws(InvalidAccessToken::class, 'expired');
