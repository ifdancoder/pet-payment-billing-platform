<?php

namespace Platform\Auth\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMerchantTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $principal = $request->attributes->get(AuthenticateAccessToken::ATTRIBUTE);
        if (! $principal instanceof AuthenticatedPrincipal) {
            return response()->json(['message' => 'An authenticated principal is required.'], 401);
        }

        $routeMerchant = $request->route('merchant');
        if (is_string($routeMerchant)
            && $principal->claims->merchantId !== '*'
            && ! hash_equals($principal->claims->merchantId, $routeMerchant)) {
            return response()->json(['message' => 'The token does not grant access to this merchant.'], 403);
        }

        return $next($request);
    }
}
