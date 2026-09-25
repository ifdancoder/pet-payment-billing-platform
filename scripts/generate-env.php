<?php

declare(strict_types=1);

if (! extension_loaded('sodium')) {
    fwrite(STDERR, "The sodium PHP extension is required.\n");
    exit(1);
}

function base64Url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function uuidV4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
}

$keypair = sodium_crypto_sign_keypair();
$secretKey = sodium_crypto_sign_secretkey($keypair);
$publicKey = sodium_crypto_sign_publickey($keypair);
$now = time();
$header = base64Url(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => 'identity-ed25519-v1'], JSON_THROW_ON_ERROR));
$payload = base64Url(json_encode([
    'iss' => 'pet-payment-identity',
    'aud' => 'pet-payment-api',
    'sub' => 'local-service',
    'merchant_id' => '*',
    'role' => 'service',
    'jti' => uuidV4(),
    'iat' => $now,
    'exp' => $now + 86400,
    'actor_type' => 'service',
    'scopes' => ['internal:http'],
], JSON_THROW_ON_ERROR));
$signingInput = $header.'.'.$payload;
$serviceToken = $signingInput.'.'.base64Url(sodium_crypto_sign_detached($signingInput, $secretKey));

$values = [
    'COMPOSE_PROJECT_NAME' => 'pet-payment-billing-platform',
    'GATEWAY_HTTP_PORT' => '8080',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'IDENTITY_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'CUSTOMER_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'CATALOG_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'SUBSCRIPTION_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'BILLING_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'PAYMENT_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'NOTIFICATION_APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'AUTH_ED25519_PUBLIC_KEY_BASE64' => base64_encode($publicKey),
    'AUTH_ED25519_SECRET_KEY_BASE64' => base64_encode($secretKey),
    'INTERNAL_SERVICE_ACCESS_TOKEN' => $serviceToken,
    'POSTGRES_HOST' => 'postgres',
    'POSTGRES_PORT' => '5432',
    'POSTGRES_USER' => 'billing',
    'POSTGRES_PASSWORD' => bin2hex(random_bytes(24)),
    'POSTGRES_DB' => 'billing',
    'RABBITMQ_HOST' => 'rabbitmq',
    'RABBITMQ_PORT' => '5672',
    'RABBITMQ_MANAGEMENT_PORT' => '15672',
    'RABBITMQ_DEFAULT_USER' => 'billing',
    'RABBITMQ_DEFAULT_PASS' => bin2hex(random_bytes(24)),
    'REDIS_HOST' => 'redis',
    'REDIS_PORT' => '6379',
];

foreach ($values as $name => $value) {
    echo $name.'='.$value.PHP_EOL;
}
