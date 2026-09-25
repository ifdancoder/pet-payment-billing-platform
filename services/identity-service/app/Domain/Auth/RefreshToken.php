<?php

namespace App\Domain\Auth;

use DateTimeImmutable;

final class RefreshToken
{
    private function __construct(
        private readonly string $id,
        private readonly string $familyId,
        private readonly string $userId,
        private readonly string $merchantId,
        private readonly string $role,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
        private ?string $replacedById,
    ) {}

    public static function issue(
        string $id,
        string $familyId,
        string $userId,
        string $merchantId,
        string $role,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self($id, $familyId, $userId, $merchantId, $role, $tokenHash, $expiresAt, null, null);
    }

    public static function reconstitute(
        string $id,
        string $familyId,
        string $userId,
        string $merchantId,
        string $role,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $revokedAt,
        ?string $replacedById,
    ): self {
        return new self($id, $familyId, $userId, $merchantId, $role, $tokenHash, $expiresAt, $revokedAt, $replacedById);
    }

    public function rotate(string $replacementId, DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
        $this->replacedById = $replacementId;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function familyId(): string
    {
        return $this->familyId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function merchantId(): string
    {
        return $this->merchantId;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function replacedById(): ?string
    {
        return $this->replacedById;
    }
}
