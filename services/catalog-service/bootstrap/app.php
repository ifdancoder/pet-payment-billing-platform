<?php

use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Product\Exceptions\ArchivedProductCannotBeModified;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\Exceptions\ProductNotFound;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (ProductNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (PriceNotFound $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (ProductAlreadyArchived $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (ArchivedProductCannotBeModified $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
