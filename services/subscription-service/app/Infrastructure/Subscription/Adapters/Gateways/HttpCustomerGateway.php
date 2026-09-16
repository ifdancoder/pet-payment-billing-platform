<?php

namespace App\Infrastructure\Subscription\Adapters\Gateways;

use App\Application\Subscription\DataTransferObjects\CustomerData;
use App\Application\Subscription\Ports\Outbound\ICustomerGatewayPort;
use App\Domain\Subscription\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Http;

final class HttpCustomerGateway implements ICustomerGatewayPort
{
    public function __construct(private readonly string $baseUrl) {}

    public function find(CustomerId $id): ?CustomerData
    {
        $response = Http::baseUrl($this->baseUrl)->get("/api/v1/customers/{$id->toString()}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json();

        return new CustomerData($body['id'], $body['email'], $body['name']);
    }
}
