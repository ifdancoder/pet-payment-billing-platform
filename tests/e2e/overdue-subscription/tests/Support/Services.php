<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Services
{
    private static ?string $accessToken = null;

    public static function authenticate(string $accessToken): void
    {
        self::$accessToken = $accessToken;
    }

    public static function identity(): Client { return self::client('IDENTITY_SERVICE_URL', 'http://localhost:18000'); }

    public static function customer(): Client { return self::client('CUSTOMER_SERVICE_URL', 'http://localhost:18001'); }

    public static function catalog(): Client { return self::client('CATALOG_SERVICE_URL', 'http://localhost:18002'); }

    public static function subscription(): Client { return self::client('SUBSCRIPTION_SERVICE_URL', 'http://localhost:18003'); }

    public static function billing(): Client { return self::client('BILLING_SERVICE_URL', 'http://localhost:18004'); }

    public static function payment(): Client { return self::client('PAYMENT_SERVICE_URL', 'http://localhost:18005'); }

    private static function client(string $envVar, string $default): Client
    {
        return new Client([
            'base_uri' => getenv($envVar) ?: $default,
            'headers' => ['Authorization' => 'Bearer '.(self::$accessToken ?? \BillingPlatform\TestSupport\TestAccessToken::value())],
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
