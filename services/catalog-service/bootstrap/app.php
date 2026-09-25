<?php

use App\Domain\Price\Exceptions\PriceAlreadyActive;
use App\Domain\Price\Exceptions\PriceAlreadyInactive;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Product\Exceptions\ArchivedProductCannotBeModified;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\Exceptions\ProductNotFound;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Platform\Auth\Laravel\AuthenticateAccessToken;
use Platform\Auth\Laravel\EnsureMerchantTenant;
use Platform\Auth\Laravel\RequireRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/health.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'access-token' => AuthenticateAccessToken::class,
            'merchant-tenant' => EnsureMerchantTenant::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (ProductNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (PriceNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (ProductAlreadyArchived $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (ArchivedProductCannotBeModified $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (PriceAlreadyActive $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (PriceAlreadyInactive $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
