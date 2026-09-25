<?php

namespace App\Application\Auth\DataTransferObjects;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public string $userId,
        public string $merchantId,
        public string $role,
    ) {}
}
