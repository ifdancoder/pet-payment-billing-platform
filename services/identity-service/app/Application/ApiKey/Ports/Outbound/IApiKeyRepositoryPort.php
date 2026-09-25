<?php

namespace App\Application\ApiKey\Ports\Outbound;

use App\Domain\ApiKey\ApiKey;

interface IApiKeyRepositoryPort
{
    public function save(ApiKey $apiKey): void;
    public function find(string $id): ?ApiKey;
    public function findForMerchant(string $id, string $merchantId): ?ApiKey;
    /** @return array<int, ApiKey> */
    public function listForMerchant(string $merchantId): array;
}
