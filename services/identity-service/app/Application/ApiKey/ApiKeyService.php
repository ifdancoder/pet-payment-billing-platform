<?php

namespace App\Application\ApiKey;

use App\Application\ApiKey\Ports\Outbound\IApiKeyRepositoryPort;
use App\Application\Auth\Ports\Outbound\IAccessTokenIssuerPort;
use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Domain\ApiKey\ApiKey;
use App\Domain\ApiKey\Exceptions\ApiKeyNotFound;
use App\Domain\ApiKey\Exceptions\InvalidApiKey;
use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantStatus;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class ApiKeyService
{
    public function __construct(
        private readonly IApiKeyRepositoryPort $repository,
        private readonly IAccessTokenIssuerPort $accessTokens,
        private readonly IMerchantRepositoryPort $merchants,
    ) {}

    /** @return array{api_key: ApiKey, secret: string} */
    public function create(string $merchantId, string $name, string $role, array $scopes): array
    {
        $id = (string) Str::uuid();
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $plain = "pk_{$id}_{$secret}";
        $apiKey = new ApiKey($id, $merchantId, $name, hash('sha256', $plain), $role, array_values(array_unique($scopes)));
        $this->repository->save($apiKey);

        return ['api_key' => $apiKey, 'secret' => $plain];
    }

    public function exchange(string $plain): array
    {
        if (! preg_match('/^pk_([0-9a-f-]{36})_[A-Za-z0-9_-]+$/i', $plain, $matches)) {
            throw new InvalidApiKey;
        }

        $apiKey = $this->repository->find($matches[1]);
        if ($apiKey === null || $apiKey->isRevoked() || ! hash_equals($apiKey->secretHash(), hash('sha256', $plain))) {
            throw new InvalidApiKey;
        }

        try {
            $merchant = $this->merchants->get(MerchantId::fromString($apiKey->merchantId()));
        } catch (MerchantNotFound) {
            throw new InvalidApiKey;
        }
        if ($merchant->status() !== MerchantStatus::Active) {
            throw new InvalidApiKey;
        }

        $apiKey->markUsed(new DateTimeImmutable);
        $this->repository->save($apiKey);

        return [
            'token_type' => 'Bearer',
            'access_token' => $this->accessTokens->issue($apiKey->id(), $apiKey->merchantId(), $apiKey->role(), 'api_key', $apiKey->scopes()),
            'expires_in' => $this->accessTokens->expiresIn(),
            'merchant_id' => $apiKey->merchantId(),
            'role' => $apiKey->role(),
            'scopes' => $apiKey->scopes(),
        ];
    }

    /** @return array<int, ApiKey> */
    public function list(string $merchantId): array { return $this->repository->listForMerchant($merchantId); }

    public function revoke(string $merchantId, string $id): void
    {
        $apiKey = $this->repository->findForMerchant($id, $merchantId);
        if ($apiKey === null) { throw new ApiKeyNotFound; }
        $apiKey->revoke(new DateTimeImmutable);
        $this->repository->save($apiKey);
    }
}
