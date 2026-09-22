<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * The one HTTP client this whole suite uses — every test goes in
 * through the real Ingress, at the real public `/v1/...` paths, the
 * same way an actual client would. Never a per-service base URL like
 * tests/e2e/*\/'s Services class uses: proving the Ingress and gateway
 * route correctly is the whole point of this suite, so bypassing them
 * would defeat it.
 */
final class Api
{
    public static function client(): Client
    {
        $host = getenv('GATEWAY_HOST') ?: 'api.pet-payment-billing-platform.local';

        // A per-request middleware, not the `headers` default array —
        // confirmed live that Guzzle's own Host-from-URI logic silently
        // wins over a default-config Host header (curl with an explicit
        // -H "Host: ..." worked; a Guzzle client-level default header
        // to the same effect did not), so this is forced on every
        // request instead of trusted to client config.
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(
            fn (RequestInterface $request): RequestInterface => $request->withHeader('Host', $host),
        ));

        return new Client([
            // kind-cluster.yaml maps the Ingress controller's hostPort
            // 80 to this host port; GATEWAY_HOST above is the Ingress
            // rule's own host, resolved via the Host header rather
            // than real DNS — no /etc/hosts entry needed.
            'base_uri' => getenv('GATEWAY_URL') ?: 'http://localhost:8090',
            'handler' => $stack,
            'http_errors' => false,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 5,
        ]);
    }
}
