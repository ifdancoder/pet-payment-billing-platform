<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

final class Api
{
    private static ?string $accessToken = null;

    public static function authenticate(string $accessToken): void
    {
        self::$accessToken = $accessToken;
    }

    public static function client(): Client
    {
        $host = getenv('GATEWAY_HOST') ?: 'api.pet-payment-billing-platform.local';

        $stack = HandlerStack::create();
        // Guzzle otherwise derives Host from base_uri instead of the ingress rule.
        $stack->push(Middleware::mapRequest(
            function (RequestInterface $request) use ($host): RequestInterface {
                $request = $request->withHeader('Host', $host);

                return self::$accessToken === null ? $request : $request->withHeader('Authorization', 'Bearer '.self::$accessToken);
            },
        ));

        return new Client([
            'base_uri' => getenv('GATEWAY_URL') ?: 'http://localhost:8090',
            'handler' => $stack,
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
