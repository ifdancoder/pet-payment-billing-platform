<?php

namespace App\Infrastructure\Subscription\Adapters\Gateways;

use App\Application\Subscription\DataTransferObjects\CustomerData;
use App\Application\Subscription\Ports\Outbound\ICustomerGatewayPort;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;
use Platform\Auth\Laravel\CorrelationIdMiddleware;

final class HttpCustomerGateway implements ICustomerGatewayPort
{
    public function __construct(private readonly string $baseUrl) {}

    public function find(MerchantId $merchantId, CustomerId $id): ?CustomerData
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->accessToken())
            ->withHeaders($this->correlationHeaders())
            ->get("/api/v1/merchants/{$merchantId->toString()}/customers/{$id->toString()}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json('data');

        return new CustomerData($body['id'], $body['email'], $body['name']);
    }

    private function accessToken(): string
    {
        return request()->bearerToken() ?? (string) config('services.internal_access_token');
    }

    private function correlationHeaders(): array
    {
        $id = request()->attributes->get(CorrelationIdMiddleware::ATTRIBUTE);
        return is_string($id) ? [CorrelationIdMiddleware::HEADER => $id] : [];
    }
}
