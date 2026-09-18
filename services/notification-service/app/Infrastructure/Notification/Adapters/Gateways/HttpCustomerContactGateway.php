<?php

namespace App\Infrastructure\Notification\Adapters\Gateways;

use App\Application\Notification\DataTransferObjects\CustomerContact;
use App\Application\Notification\Ports\Outbound\ICustomerContactGatewayPort;
use Illuminate\Support\Facades\Http;

final class HttpCustomerContactGateway implements ICustomerContactGatewayPort
{
    public function __construct(private readonly string $baseUrl) {}

    public function find(string $customerId): ?CustomerContact
    {
        $response = Http::baseUrl($this->baseUrl)->get("/api/v1/customers/{$customerId}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json();

        return new CustomerContact($body['id'], $body['email'], $body['name']);
    }
}
