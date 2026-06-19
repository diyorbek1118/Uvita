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
    /**
     * Report qilinmaydigan (logga yozilmaydigan) xatoliklar ro'yxati.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * Ilova uchun xatoliklarni boshqarish qoidalarini ro'yxatdan o'tkazish.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e) {

            // 1. Validatsiya xatoliklari (FormRequest yoki $request->validate())
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validatsiya xatosi.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // 2. Autentifikatsiya xatosi (Sanctum yoki Bearer token xato bo'lsa)
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Tizimga kirish talab etiladi.', 401);
            }

            // 3. Model topilmadi (Masalan: User::findOrFail($id))
            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('Resurs topilmadi.', 404);
            }

            // 4. API Endpoint (Route) topilmadi
            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('Endpoint topilmadi.', 404);
            }

            // 5. Umumiy HTTP xatolari (403 Forbidden, 429 Too Many Requests va h.k.)
            if ($e instanceof HttpException) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'Tizim taqiqlagan so\'rov.', 
                    $e->getStatusCode()
                );
            }

            // 6. Biznes mantiq xatoliklari (Service-lardan otilgan custom throw new \Exceptionlar)
            if ($e instanceof \Exception) {
                $code = $e->getCode();
                // Agar kod HTTP statusga to'g'ri kelmasa, default 422 (Unprocessable Entity) qaytaramiz
                $httpCode = ($code >= 400 && $code < 600) ? $code : 422;

                return ApiResponse::error($e->getMessage(), $httpCode);
            }

            // 7. Fatal Error yoki Kutilmagan boshqa har qanday xatolik (Throwable)
            // Agar localda bo'lsangiz (debug true) va kutilmagan xato bo'lsa, Laravel debug trace-ni ko'rsatadi.
            // Agar productionda bo'lsa, chiroyli "Server xatoligi" qaytadi.
            if (!config('app.debug')) {
                return ApiResponse::error('Serverda ichki xatolik yuz berdi.', 500);
            }
        });
    }
}