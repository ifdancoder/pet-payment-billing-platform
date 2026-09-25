<?php

namespace App\Infrastructure\Auth\Adapters\Security;

use App\Application\Auth\Ports\Outbound\IAccessTokenIssuerPort;
use Illuminate\Support\Str;
use Platform\Auth\Ed25519AccessTokenCodec;

final class Ed25519AccessTokenIssuer implements IAccessTokenIssuerPort
{
    public function __construct(private readonly Ed25519AccessTokenCodec $codec) {}

    public function issue(
        string $subject,
        string $merchantId,
        string $role,
        string $actorType = 'user',
        array $scopes = [],
    ): string {
        return $this->codec->issue($subject, $merchantId, $role, (string) Str::uuid(), actorType: $actorType, scopes: $scopes);
    }

    public function expiresIn(): int
    {
        return (int) config('auth_tokens.access_ttl_seconds');
    }
}
