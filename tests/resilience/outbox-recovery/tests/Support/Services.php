<?php

namespace Tests\Support;

use BillingPlatform\TestSupport\TestAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Services
{
    public static function billing(): Client
    {
        return self::client('BILLING_SERVICE_URL', 'http://localhost:18004');
    }

    private static function client(string $envVar, string $default): Client
    {
        return new Client([
            'base_uri' => getenv($envVar) ?: $default,
            'headers' => ['Authorization' => 'Bearer '.TestAccessToken::value()],
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
