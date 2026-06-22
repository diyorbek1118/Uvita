<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Commands\SendOtpCommand;
use Modules\Auth\Application\Commands\VerifyOtpCommand;
use Modules\Auth\Application\Handlers\SendOtpHandler;
use Modules\Auth\Application\Handlers\VerifyOtpHandler;
use Modules\Auth\Presentation\Requests\SendOtpRequest;
use Modules\Auth\Presentation\Requests\VerifyOtpRequest;
use Modules\User\Domain\Entities\User as UserEntity;

final class AuthController extends Controller
{
    public function __construct(
        private readonly SendOtpHandler  $sendOtpHandler,
        private readonly VerifyOtpHandler $verifyOtpHandler,
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->sendOtpHandler->handle(
            SendOtpCommand::fromRequest($request)
        );

        return response()->json(['message' => 'SMS yuborildi']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->verifyOtpHandler->handle(
            VerifyOtpCommand::fromRequest($request)
        );

        $isNew      = $result['is_new'];
        $statusCode = $isNew ? 201 : 200;

        return response()->json([
            'data'  => [
                'token' => $result['token'],
                'user'  => $this->formatUser($result['user']),
            ],
            'isNew' => $isNew,
        ], $statusCode);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Tizimdan chiqildi']);
    }

    private function formatUser(UserEntity $user): array
    {
        return [
            'id'    => $user->id,
            'phone' => $user->phone,
            'name'  => $user->name,
        ];
    }
}
