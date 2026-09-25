<?php

namespace App\Application\Auth\Ports\Outbound;

use App\Domain\Auth\RefreshToken;

interface IRefreshTokenRepositoryPort
{
    public function save(RefreshToken $token): void;

    public function findByHash(string $tokenHash): ?RefreshToken;

    public function revokeFamily(string $familyId): void;
}
