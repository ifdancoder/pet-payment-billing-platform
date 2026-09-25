<?php

namespace App\Application\Auth\Ports\Outbound;

interface IAccessTokenIssuerPort
{
    public function issue(
        string $subject,
        string $merchantId,
        string $role,
        string $actorType = 'user',
        array $scopes = [],
    ): string;

    public function expiresIn(): int;
}
