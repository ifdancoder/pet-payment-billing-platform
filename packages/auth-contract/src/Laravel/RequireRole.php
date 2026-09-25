<?php

namespace Platform\Auth\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $principal = $request->attributes->get(AuthenticateAccessToken::ATTRIBUTE);
        if (! $principal instanceof AuthenticatedPrincipal) {
            return response()->json(['message' => 'An authenticated principal is required.'], 401);
        }

        if ($principal->isService() || in_array($principal->claims->role, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'The authenticated principal is not allowed to perform this action.'], 403);
    }
}
