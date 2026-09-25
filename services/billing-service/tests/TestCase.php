<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Platform\Auth\Ed25519AccessTokenCodec;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateAs('*');
    }

    protected function authenticateAs(string $merchantId, string $role = 'owner'): static
    {
        $token = app(Ed25519AccessTokenCodec::class)->issue('test-user', $merchantId, $role, (string) Str::uuid());

        return $this->withToken($token);
    }
}
