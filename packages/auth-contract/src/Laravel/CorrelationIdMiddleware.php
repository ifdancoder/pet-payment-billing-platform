<?php

namespace Platform\Auth\Laravel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CorrelationIdMiddleware
{
    public const ATTRIBUTE = 'billing.correlation_id';
    public const HEADER = 'X-Correlation-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->headers->get(self::HEADER);
        $correlationId = is_string($candidate) && Str::isUuid($candidate)
            ? strtolower($candidate)
            : (string) Str::uuid();
        $request->attributes->set(self::ATTRIBUTE, $correlationId);
        Log::withContext(['correlation_id' => $correlationId, 'http_method' => $request->method(), 'http_path' => '/'.$request->path()]);

        $response = $next($request);
        $response->headers->set(self::HEADER, $correlationId);
        return $response;
    }
}
