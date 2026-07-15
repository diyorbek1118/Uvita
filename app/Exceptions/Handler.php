<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Shared\Exceptions\DomainException;
use App\Shared\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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

            // 1. Validatsiya xatoliklari — FormRequest yoki $request->validate()
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?? "Ma'lumotlar noto'g'ri.",
                    'errors'  => $e->errors(),
                ], 422);
            }

            // 2. Autentifikatsiya xatosi — Sanctum / Bearer token yo'q yoki muddati o'tgan
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Autentifikatsiya talab qilinadi.', 401);
            }

            // 3. Model topilmadi — findOrFail() dan
            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('Resurs topilmadi.', 404);
            }

            // 4. Route topilmadi
            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('Endpoint topilmadi.', 404);
            }

            // 5. Database xatoligi — SQL, ulanish va boshqa DB xatolari
            //    Raw SQL, host, port, database nomi clientga ko'rsatilmaydi
            if ($e instanceof QueryException) {
                return ApiResponse::error('Tizim xatosi yuz berdi.', 500);
            }

            // 6. Umumiy HTTP xatolari — 403, 429 va boshqalar
            if ($e instanceof HttpException) {
                return ApiResponse::error(
                    $e->getMessage() ?: "So'rov bajarilmadi.",
                    $e->getStatusCode()
                );
            }

            // 7. Domain (biznes mantiq) xatoliklari — bootstrap/app.php da ro'yxatlanmaganlar uchun
            if ($e instanceof DomainException) {
                return ApiResponse::error($e->getMessage(), 422);
            }

            // 8. Kutilmagan boshqa barcha xatoliklar — tafsilotlar yashiriladi
            if (!config('app.debug')) {
                return ApiResponse::error('Tizim xatosi yuz berdi.', 500);
            }

            // Debug rejimida Laravel standart debug ekranini ko'rsatadi
        });
    }
}
