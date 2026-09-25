<?php

namespace App\Domain\ApiKey;

use DateTimeImmutable;

final class ApiKey
{
    public function __construct(
        private readonly string $id,
        private readonly string $merchantId,
        private readonly string $name,
        private readonly string $secretHash,
        private readonly string $role,
        private readonly array $scopes,
        private ?DateTimeImmutable $revokedAt = null,
        private ?DateTimeImmutable $lastUsedAt = null,
    ) {}

    public function id(): string { return $this->id; }
    public function merchantId(): string { return $this->merchantId; }
    public function name(): string { return $this->name; }
    public function secretHash(): string { return $this->secretHash; }
    public function role(): string { return $this->role; }
    public function scopes(): array { return $this->scopes; }
    public function revokedAt(): ?DateTimeImmutable { return $this->revokedAt; }
    public function lastUsedAt(): ?DateTimeImmutable { return $this->lastUsedAt; }
    public function isRevoked(): bool { return $this->revokedAt !== null; }
    public function revoke(DateTimeImmutable $at): void { $this->revokedAt ??= $at; }
    public function markUsed(DateTimeImmutable $at): void { $this->lastUsedAt = $at; }
}
