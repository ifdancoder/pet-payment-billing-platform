<?php

namespace Tests\Support;

use BillingPlatform\TestSupport\TestAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Services
{
    public static function subscription(): Client
    {
        return self::client('SUBSCRIPTION_SERVICE_URL', 'http://localhost:18003');
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
