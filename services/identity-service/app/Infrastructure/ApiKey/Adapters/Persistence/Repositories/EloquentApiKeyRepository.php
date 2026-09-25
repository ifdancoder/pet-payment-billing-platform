<?php

namespace App\Infrastructure\ApiKey\Adapters\Persistence\Repositories;

use App\Application\ApiKey\Ports\Outbound\IApiKeyRepositoryPort;
use App\Domain\ApiKey\ApiKey;
use App\Infrastructure\ApiKey\Adapters\Persistence\Models\ApiKeyModel;

final class EloquentApiKeyRepository implements IApiKeyRepositoryPort
{
    public function save(ApiKey $apiKey): void
    {
        ApiKeyModel::query()->updateOrCreate(['id' => $apiKey->id()], [
            'merchant_id' => $apiKey->merchantId(), 'name' => $apiKey->name(),
            'secret_hash' => $apiKey->secretHash(), 'role' => $apiKey->role(), 'scopes' => $apiKey->scopes(),
            'revoked_at' => $apiKey->revokedAt(), 'last_used_at' => $apiKey->lastUsedAt(),
        ]);
    }

    public function find(string $id): ?ApiKey { return $this->map(ApiKeyModel::query()->find($id)); }
    public function findForMerchant(string $id, string $merchantId): ?ApiKey
    {
        return $this->map(ApiKeyModel::query()->whereKey($id)->where('merchant_id', $merchantId)->first());
    }
    public function listForMerchant(string $merchantId): array
    {
        return ApiKeyModel::query()->where('merchant_id', $merchantId)->orderBy('name')->get()->map(fn ($m) => $this->map($m))->all();
    }
    private function map(?ApiKeyModel $model): ?ApiKey
    {
        return $model === null ? null : new ApiKey($model->id, $model->merchant_id, $model->name, $model->secret_hash, $model->role, $model->scopes ?? [], $model->revoked_at?->toDateTimeImmutable(), $model->last_used_at?->toDateTimeImmutable());
    }
}
