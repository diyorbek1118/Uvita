<?php

namespace App\Exceptions;

use App\Shared\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    public function register(): void
    {
        $this->renderable(function (Throwable $e) {

            // Validation xatosi
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xatosi.',
                    'data'    => $e->errors(),
                ], 422);
            }

            // Autentifikatsiya xatosi
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Tizimga kirish talab etiladi.', 401);
            }

            // Model topilmadi
            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('Resurs topilmadi.', 404);
            }

            // Route topilmadi
            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('Endpoint topilmadi.', 404);
            }

            // HTTP xatolari (403, 429, va h.k.)
            if ($e instanceof HttpException) {
                return ApiResponse::error($e->getMessage() ?: 'HTTP xatosi.', $e->getStatusCode());
            }

            // Bizning \Exception lar (Service dan keladi)
            if ($e instanceof \Exception) {
                $code     = $e->getCode();
                $httpCode = ($code >= 400 && $code < 600) ? $code : 500;

                return ApiResponse::error($e->getMessage(), $httpCode);
            }
        });
    }
}