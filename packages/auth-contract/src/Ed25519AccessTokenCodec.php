<?php

namespace Platform\Auth;

use JsonException;

final class Ed25519AccessTokenCodec
{
    private readonly string $publicKey;

    private readonly ?string $secretKey;

    public function __construct(
        private readonly string $issuer,
        private readonly string $audience,
        string $publicKeyBase64,
        ?string $secretKeyBase64 = null,
        private readonly int $timeToLiveSeconds = 900,
        private readonly int $clockLeewaySeconds = 30,
        private readonly string $keyId = 'identity-ed25519-v1',
    ) {
        $this->publicKey = self::decodeKey($publicKeyBase64, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, 'public');
        $this->secretKey = $secretKeyBase64 === null || $secretKeyBase64 === ''
            ? null
            : self::decodeKey($secretKeyBase64, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, 'secret');

        if ($this->timeToLiveSeconds <= 0) {
            throw new \InvalidArgumentException('Access-token TTL must be positive.');
        }
    }

    public function issue(
        string $userId,
        string $merchantId,
        string $role,
        string $tokenId,
        ?int $now = null,
        string $actorType = 'user',
        array $scopes = [],
    ): string {
        if ($this->secretKey === null) {
            throw new \LogicException('This token codec was configured for verification only.');
        }

        $issuedAt = $now ?? time();
        $claims = new AccessTokenClaims(
            $this->issuer,
            $this->audience,
            $userId,
            $merchantId,
            $role,
            $tokenId,
            $issuedAt,
            $issuedAt + $this->timeToLiveSeconds,
            $actorType,
            $scopes,
        );

        $header = self::encodeJson(['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => $this->keyId]);
        $payload = self::encodeJson($claims->toArray());
        $signingInput = $header.'.'.$payload;
        $signature = sodium_crypto_sign_detached($signingInput, $this->secretKey);

        return $signingInput.'.'.self::base64UrlEncode($signature);
    }

    public function verify(string $token, ?int $now = null): AccessTokenClaims
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw InvalidAccessToken::because('A token must contain exactly three segments.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = self::decodeJson($encodedHeader);
        if (($header['alg'] ?? null) !== 'EdDSA' || ($header['typ'] ?? null) !== 'JWT') {
            throw InvalidAccessToken::because('Unsupported token header.');
        }

        if (($header['kid'] ?? null) !== $this->keyId) {
            throw InvalidAccessToken::because('Unknown signing key.');
        }

        $signature = self::base64UrlDecode($encodedSignature);
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $encodedHeader.'.'.$encodedPayload, $this->publicKey)) {
            throw InvalidAccessToken::because('The token signature is invalid.');
        }

        $claims = AccessTokenClaims::fromArray(self::decodeJson($encodedPayload));
        if (! hash_equals($this->issuer, $claims->issuer) || ! hash_equals($this->audience, $claims->audience)) {
            throw InvalidAccessToken::because('The token issuer or audience is invalid.');
        }

        $currentTime = $now ?? time();
        if ($claims->issuedAt > $currentTime + $this->clockLeewaySeconds) {
            throw InvalidAccessToken::because('The token was issued in the future.');
        }

        if ($claims->expiresAt <= $currentTime - $this->clockLeewaySeconds) {
            throw InvalidAccessToken::because('The token has expired.');
        }

        if ($claims->expiresAt <= $claims->issuedAt) {
            throw InvalidAccessToken::because('The token lifetime is invalid.');
        }

        return $claims;
    }

    private static function decodeKey(string $encoded, int $expectedLength, string $name): string
    {
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== $expectedLength) {
            throw new \InvalidArgumentException("The Ed25519 {$name} key is invalid.");
        }

        return $key;
    }

    /** @param array<string, mixed> $value */
    private static function encodeJson(array $value): string
    {
        try {
            return self::base64UrlEncode(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } catch (JsonException $exception) {
            throw InvalidAccessToken::because($exception->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private static function decodeJson(string $encoded): array
    {
        try {
            $value = json_decode(self::base64UrlDecode($encoded), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidAccessToken::because($exception->getMessage());
        }

        if (! is_array($value)) {
            throw InvalidAccessToken::because('A token segment must contain a JSON object.');
        }

        return $value;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            throw InvalidAccessToken::because('A token segment is not valid base64url.');
        }

        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);
        if ($decoded === false) {
            throw InvalidAccessToken::because('A token segment is not valid base64url.');
        }

        return $decoded;
    }
}
