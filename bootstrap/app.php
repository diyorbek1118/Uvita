<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsCourier;
use App\Http\Middleware\EnsureIsManager;
use App\Http\Middleware\EnsureIsSuperAdmin;
use App\Shared\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\OtpRateLimitException;
use Modules\Payment\Domain\Exceptions\DuplicateTransactionException;
use Modules\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use Modules\Payment\Domain\Exceptions\InvalidSignatureException;
use Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use Modules\Review\Domain\Exceptions\OrderNotDeliveredException;
use Modules\Review\Domain\Exceptions\ReviewAlreadyExistsException;
use Modules\Review\Domain\Exceptions\ReviewNotFoundException;
use Modules\Product\Domain\Exceptions\InsufficientStockException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

        $middleware->alias([
            'role.manager'    => EnsureIsManager::class,
            'role.courier'    => EnsureIsCourier::class,
            'role.admin'      => EnsureIsAdmin::class,
            'role.super_admin' => EnsureIsSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // --- Spetsifik domain exceptionlar ---

        $exceptions->render(function (InvalidOtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (OtpRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        });

        $exceptions->render(function (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (InvalidSignatureException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        });

        $exceptions->render(function (PaymentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });

        $exceptions->render(function (DuplicateTransactionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (InvalidPaymentAmountException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (ReviewNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });

        $exceptions->render(function (ReviewAlreadyExistsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (OrderNotDeliveredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        // --- Infrastruktura va tizim xatoliklari ---

        // DB ulanish yoki query xatoligi
        $exceptions->render(function (QueryException $e) {
            if (config('app.debug')) {
                return response()->json([
                    'message'   => $e->getMessage(),
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ], 500);
            }

            return response()->json(['message' => 'Tizim xatosi yuz berdi.'], 500);
        });

        // --- HTTP va umumiy xatoliklar ---

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json(['message' => 'Autentifikatsiya talab qilinadi.'], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json(['message' => 'Resurs topilmadi.'], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(['message' => 'Endpoint topilmadi.'], 404);
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => "Ma'lumotlar noto'g'ri.",
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (HttpException $e) {
            return response()->json(
                ['message' => $e->getMessage() ?: "So'rov bajarilmadi."],
                $e->getStatusCode()
            );
        });

        // Barcha boshqa DomainException lar (bootstrap da ro'yxatlanmaganlar)
        $exceptions->render(function (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        // Kutilmagan har qanday xatolik
        $exceptions->render(function (\Throwable $e) {
            if (config('app.debug')) {
                return response()->json([
                    'message'   => $e->getMessage(),
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ], 500);
            }

            return response()->json(['message' => 'Tizim xatosi yuz berdi.'], 500);
        });
    })
    ->create();
