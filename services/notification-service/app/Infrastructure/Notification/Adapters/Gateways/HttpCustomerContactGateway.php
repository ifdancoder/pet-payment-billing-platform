<?php

namespace App\Infrastructure\Notification\Adapters\Gateways;

use App\Application\Notification\DataTransferObjects\CustomerContact;
use App\Application\Notification\Ports\Outbound\ICustomerContactGatewayPort;
use Illuminate\Support\Facades\Http;
use Platform\Auth\Laravel\CorrelationIdMiddleware;

final class HttpCustomerContactGateway implements ICustomerContactGatewayPort
{
    public function __construct(private readonly string $baseUrl) {}

    public function find(string $merchantId, string $customerId): ?CustomerContact
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken((string) config('services.internal_access_token'))
            ->withHeaders($this->correlationHeaders())
            ->get("/api/v1/merchants/{$merchantId}/customers/{$customerId}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json('data');

        return new CustomerContact($body['id'], $body['email'], $body['name']);
    }

    private function correlationHeaders(): array
    {
        if (! app()->bound('request')) {
            return [];
        }
        $id = request()->attributes->get(CorrelationIdMiddleware::ATTRIBUTE);
        return is_string($id) ? [CorrelationIdMiddleware::HEADER => $id] : [];
    }
}
