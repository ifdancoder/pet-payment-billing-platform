<?php

namespace BillingPlatform\TestSupport;

final class TestAccessToken
{
    public static function value(): string
    {
        $token = getenv('INTERNAL_SERVICE_ACCESS_TOKEN');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('INTERNAL_SERVICE_ACCESS_TOKEN must be generated for Compose tests.');
        }

        return $token;
    }
}
