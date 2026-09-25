<?php

declare(strict_types=1);

require getcwd().'/vendor/autoload.php';

$setEnvironment = static function (string $name, string $value): void {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
};

if (! getenv('AUTH_ED25519_SECRET_KEY_BASE64') || ! getenv('AUTH_ED25519_PUBLIC_KEY_BASE64')) {
    $keypair = sodium_crypto_sign_keypair();
    $setEnvironment('AUTH_ED25519_SECRET_KEY_BASE64', base64_encode(sodium_crypto_sign_secretkey($keypair)));
    $setEnvironment('AUTH_ED25519_PUBLIC_KEY_BASE64', base64_encode(sodium_crypto_sign_publickey($keypair)));
}

if (! getenv('APP_KEY')) {
    $setEnvironment('APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
}
