<?php

namespace Platform\Auth\Laravel;

use Platform\Auth\AccessTokenClaims;

final readonly class AuthenticatedPrincipal
{
    public function __construct(public AccessTokenClaims $claims) {}

    public function isService(): bool
    {
        return $this->claims->actorType === 'service';
    }
}
