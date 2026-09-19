<?php

use App\Domain\Invoice\Exceptions\InvalidInvoiceTransition;
use App\Domain\Invoice\Exceptions\InvoiceAlreadyVoided;
use App\Domain\Invoice\Exceptions\InvoiceNotFound;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/health.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (InvoiceNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (InvoiceAlreadyVoided $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (InvalidInvoiceTransition $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
