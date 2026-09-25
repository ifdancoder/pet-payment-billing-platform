<?php

use App\Application\Subscription\Exceptions\CustomerNotFound;
use App\Application\Subscription\Exceptions\PriceIsNotActive;
use App\Application\Subscription\Exceptions\PriceIsNotRecurring;
use App\Application\Subscription\Exceptions\PriceNotFound;
use App\Domain\Subscription\Exceptions\InvalidSubscriptionTransition;
use App\Domain\Subscription\Exceptions\SubscriptionNotFound;
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

        $exceptions->render(fn (SubscriptionNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (CustomerNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (PriceNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (PriceIsNotRecurring $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (PriceIsNotActive $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (InvalidSubscriptionTransition $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
