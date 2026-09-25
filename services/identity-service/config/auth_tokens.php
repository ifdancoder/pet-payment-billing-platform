<?php

return [
    'issuer' => env('AUTH_TOKEN_ISSUER', 'pet-payment-identity'),
    'audience' => env('AUTH_TOKEN_AUDIENCE', 'pet-payment-api'),
    'public_key' => env('AUTH_ED25519_PUBLIC_KEY_BASE64'),
    'secret_key' => env('AUTH_ED25519_SECRET_KEY_BASE64'),
    'key_id' => env('AUTH_TOKEN_KEY_ID', 'identity-ed25519-v1'),
    'access_ttl_seconds' => (int) env('AUTH_ACCESS_TTL_SECONDS', 900),
    'refresh_ttl_seconds' => (int) env('AUTH_REFRESH_TTL_SECONDS', 2592000),
    'clock_leeway_seconds' => (int) env('AUTH_CLOCK_LEEWAY_SECONDS', 30),
];
