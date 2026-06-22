<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\OtpRateLimitException;
use Modules\Product\Domain\Exceptions\InsufficientStockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
        then: function (): void {
            // DDD modullari: Modules/*/Presentation/routes/api.php
            foreach (glob(base_path('Modules/*/Presentation/routes/api.php')) as $routeFile) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($routeFile);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidOtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (OtpRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        });

        $exceptions->render(function (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(['message' => 'Resurs topilmadi.'], 404);
        });
    })
    ->create();
