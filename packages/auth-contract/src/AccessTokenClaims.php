<?php

namespace Platform\Auth;

final readonly class AccessTokenClaims
{
    public function __construct(
        public string $issuer,
        public string $audience,
        public string $subject,
        public string $merchantId,
        public string $role,
        public string $tokenId,
        public int $issuedAt,
        public int $expiresAt,
        public string $actorType = 'user',
        public array $scopes = [],
    ) {}

    /**
     * @return array{iss: string, aud: string, sub: string, merchant_id: string, role: string, jti: string, iat: int, exp: int, actor_type: string, scopes: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $this->subject,
            'merchant_id' => $this->merchantId,
            'role' => $this->role,
            'jti' => $this->tokenId,
            'iat' => $this->issuedAt,
            'exp' => $this->expiresAt,
            'actor_type' => $this->actorType,
            'scopes' => $this->scopes,
        ];
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        foreach (['iss', 'aud', 'sub', 'merchant_id', 'role', 'jti', 'iat', 'exp'] as $claim) {
            if (! array_key_exists($claim, $payload)) {
                throw InvalidAccessToken::because("Required claim '{$claim}' is missing.");
            }
        }

        foreach (['iss', 'aud', 'sub', 'merchant_id', 'role', 'jti'] as $claim) {
            if (! is_string($payload[$claim]) || $payload[$claim] === '') {
                throw InvalidAccessToken::because("Claim '{$claim}' must be a non-empty string.");
            }
        }

        if (! is_int($payload['iat']) || ! is_int($payload['exp'])) {
            throw InvalidAccessToken::because('Claims iat and exp must be integer timestamps.');
        }

        return new self(
            $payload['iss'],
            $payload['aud'],
            $payload['sub'],
            $payload['merchant_id'],
            $payload['role'],
            $payload['jti'],
            $payload['iat'],
            $payload['exp'],
            is_string($payload['actor_type'] ?? null) ? $payload['actor_type'] : 'user',
            is_array($payload['scopes'] ?? null) ? array_values(array_filter($payload['scopes'], 'is_string')) : [],
        );
    }
}
