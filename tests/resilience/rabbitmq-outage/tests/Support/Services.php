<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Thin HTTP clients for the services this scenario touches. No
 * assertions live here — just "make the call, return the decoded
 * body" — so the test itself reads as the scenario, not as HTTP
 * plumbing. Base URLs are the docker-compose.yaml port mappings,
 * overridable via env for a CI runner that publishes different ports.
 */
final class Services
{
    public static function customer(): Client
    {
        return self::client('CUSTOMER_SERVICE_URL', 'http://localhost:18001');
    }

    public static function catalog(): Client
    {
        return self::client('CATALOG_SERVICE_URL', 'http://localhost:18002');
    }

    public static function subscription(): Client
    {
        return self::client('SUBSCRIPTION_SERVICE_URL', 'http://localhost:18003');
    }

    public static function billing(): Client
    {
        return self::client('BILLING_SERVICE_URL', 'http://localhost:18004');
    }

    private static function client(string $envVar, string $default): Client
    {
        return new Client([
            'base_uri' => getenv($envVar) ?: $default,
            'headers' => ['Authorization' => 'Bearer '.\BillingPlatform\TestSupport\TestAccessToken::value()],
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
