<?php

use App\Domain\Auth\Exceptions\InvalidCredentials;
use App\Domain\Auth\Exceptions\InvalidRefreshToken;
use App\Domain\Auth\Exceptions\RefreshTokenReuseDetected;
use App\Domain\ApiKey\Exceptions\ApiKeyNotFound;
use App\Domain\ApiKey\Exceptions\InvalidApiKey;
use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\Exceptions\LastOwner;
use App\Domain\Membership\Exceptions\UserAlreadyAMember;
use App\Domain\User\Exceptions\EmailAlreadyRegistered;
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

        $exceptions->render(fn (EmailAlreadyRegistered $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (InvalidCredentials $e) => response()->json(['message' => $e->getMessage()], 401));
        $exceptions->render(fn (InvalidRefreshToken $e) => response()->json(['message' => $e->getMessage()], 401));
        $exceptions->render(fn (RefreshTokenReuseDetected $e) => response()->json(['message' => $e->getMessage()], 401));
        $exceptions->render(fn (InvalidApiKey $e) => response()->json(['message' => $e->getMessage()], 401));
        $exceptions->render(fn (ApiKeyNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (MembershipNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (UserAlreadyAMember $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (LastOwner $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
