<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Thin HTTP client for the one service this test boots. No assertions
 * live here — just "make the call, return the decoded body" — so the
 * test itself reads as the scenario, not as HTTP plumbing. Base URL is
 * the docker-compose.yaml port mapping, overridable via env for a CI
 * runner that publishes a different port.
 */
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
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
