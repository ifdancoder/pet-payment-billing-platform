<?php

namespace App\Infrastructure\Subscription\Adapters\Gateways;

use App\Application\Subscription\DataTransferObjects\PriceData;
use App\Application\Subscription\Ports\Outbound\ICatalogGatewayPort;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

final class HttpCatalogGateway implements ICatalogGatewayPort
{
    public function __construct(private readonly string $baseUrl) {}

    public function findPrice(MerchantId $merchantId, PriceId $priceId): ?PriceData
    {
        $response = Http::baseUrl($this->baseUrl)
            ->get("/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json('data');

        return new PriceData(
            $body['id'],
            $body['product_id'],
            $body['amount_minor_units'],
            $body['currency'],
            $body['type'],
            $body['billing_interval'],
            $body['billing_interval_count'],
            $body['status'],
        );
    }
}
