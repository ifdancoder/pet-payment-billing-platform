<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Platform\Auth\Ed25519AccessTokenCodec;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $token = app(Ed25519AccessTokenCodec::class)->issue(
            '00000000-0000-4000-8000-000000000001',
            '*',
            'owner',
            '00000000-0000-4000-8000-000000000002',
        );
        $this->withToken($token);
    }
}
