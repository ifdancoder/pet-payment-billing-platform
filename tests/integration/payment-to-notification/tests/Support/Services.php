<?php

namespace Tests\Support;

use BillingPlatform\TestSupport\TestAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Services
{
    public static function customer(): Client
    {
        return self::client('CUSTOMER_SERVICE_URL', 'http://localhost:18001');
    }

    public static function notification(): Client
    {
        return self::client('NOTIFICATION_SERVICE_URL', 'http://localhost:18006');
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
