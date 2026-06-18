<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\DTOs\SendOtpDTO;
use Modules\Auth\DTOs\VerifyOtpDTO;
use Modules\Auth\Requests\SendOtpRequest;
use Modules\Auth\Requests\VerifyOtpRequest;
use Modules\Auth\Services\AuthService;
use Modules\User\Resources\UserResource;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $this->authService->sendOtp(
                SendOtpDTO::fromArray($request->validated())
            );

            return ApiResponse::success(
                message: 'OTP kod yuborildi.'
            );

        } catch (\Exception $e) {
            $code = $e->getCode();
            $httpCode = ($code >= 400 && $code < 600) ? $code : 500;

            return ApiResponse::error($e->getMessage(), $httpCode);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->verifyOtp(
                VerifyOtpDTO::fromArray($request->validated())
            );

            return ApiResponse::success(
                data: [
                    'token' => $result['token'],
                    'user'  => new UserResource($result['user']),
                ],
                message: 'Muvaffaqiyatli kirildi.'
            );

        } catch (\Exception $e) {
            $code = $e->getCode();
            $httpCode = ($code >= 400 && $code < 600) ? $code : 500;

            return ApiResponse::error($e->getMessage(), $httpCode);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(message: 'Chiqildi.');
    }
}
