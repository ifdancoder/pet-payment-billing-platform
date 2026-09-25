<?php

namespace Platform\Auth\Laravel;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Auth\Ed25519AccessTokenCodec;
use Platform\Auth\InvalidAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateAccessToken
{
    public const ATTRIBUTE = 'billing.auth.principal';

    public function __construct(private readonly Ed25519AccessTokenCodec $codec) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            return $this->unauthorized('A bearer access token is required.');
        }

        try {
            $claims = $this->codec->verify($token);
        } catch (InvalidAccessToken) {
            return $this->unauthorized('The bearer access token is invalid or expired.');
        }

        $request->attributes->set(self::ATTRIBUTE, new AuthenticatedPrincipal($claims));

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
