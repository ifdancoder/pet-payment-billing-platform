<?php

namespace App\Infrastructure\Auth\Adapters\Persistence\Repositories;

use App\Application\Auth\Ports\Outbound\IRefreshTokenRepositoryPort;
use App\Domain\Auth\RefreshToken;
use App\Infrastructure\Auth\Adapters\Persistence\Models\RefreshTokenModel;
use DateTimeImmutable;

final class EloquentRefreshTokenRepository implements IRefreshTokenRepositoryPort
{
    public function save(RefreshToken $token): void
    {
        RefreshTokenModel::query()->updateOrCreate(
            ['id' => $token->id()],
            [
                'family_id' => $token->familyId(),
                'user_id' => $token->userId(),
                'merchant_id' => $token->merchantId(),
                'role' => $token->role(),
                'token_hash' => $token->tokenHash(),
                'expires_at' => $token->expiresAt(),
                'revoked_at' => $token->revokedAt(),
                'replaced_by_id' => $token->replacedById(),
            ],
        );
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        $model = RefreshTokenModel::query()->where('token_hash', $tokenHash)->lockForUpdate()->first();

        return $model === null ? null : RefreshToken::reconstitute(
            $model->id,
            $model->family_id,
            $model->user_id,
            $model->merchant_id,
            $model->role,
            $model->token_hash,
            DateTimeImmutable::createFromInterface($model->expires_at),
            $model->revoked_at === null ? null : DateTimeImmutable::createFromInterface($model->revoked_at),
            $model->replaced_by_id,
        );
    }

    public function revokeFamily(string $familyId): void
    {
        RefreshTokenModel::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
