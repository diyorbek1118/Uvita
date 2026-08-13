<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Commands\ChangePasswordCommand;
use Modules\Auth\Application\Commands\ChangePhoneCommand;
use Modules\Auth\Application\Commands\CheckPhoneCommand;
use Modules\Auth\Application\Commands\ConfirmOtpCommand;
use Modules\Auth\Application\Commands\PasswordLoginCommand;
use Modules\Auth\Application\Commands\RegisterCommand;
use Modules\Auth\Application\Commands\ResetPasswordCommand;
use Modules\Auth\Application\Commands\SendOtpCommand;
use Modules\Auth\Application\Commands\VerifyOtpCommand;
use Modules\Auth\Application\Handlers\ChangePasswordHandler;
use Modules\Auth\Application\Handlers\ChangePhoneHandler;
use Modules\Auth\Application\Handlers\CheckPhoneHandler;
use Modules\Auth\Application\Handlers\ConfirmOtpHandler;
use Modules\Auth\Application\Handlers\PasswordLoginHandler;
use Modules\Auth\Application\Handlers\RegisterHandler;
use Modules\Auth\Application\Handlers\ResetPasswordHandler;
use Modules\Auth\Application\Handlers\SendOtpHandler;
use Modules\Auth\Application\Handlers\VerifyOtpHandler;
use Modules\Auth\Presentation\Requests\ChangePasswordRequest;
use Modules\Auth\Presentation\Requests\ChangePhoneRequest;
use Modules\Auth\Presentation\Requests\CheckPhoneRequest;
use Modules\Auth\Presentation\Requests\ConfirmOtpRequest;
use Modules\Auth\Presentation\Requests\PasswordLoginRequest;
use Modules\Auth\Presentation\Requests\RegisterRequest;
use Modules\Auth\Presentation\Requests\ResetPasswordRequest;
use Modules\Auth\Presentation\Requests\SendOtpRequest;
use Modules\Auth\Presentation\Requests\VerifyOtpRequest;
use Modules\User\Domain\Entities\User as UserEntity;

final class AuthController extends Controller
{
    public function __construct(
        private readonly SendOtpHandler         $sendOtpHandler,
        private readonly VerifyOtpHandler       $verifyOtpHandler,
        private readonly ChangePasswordHandler $changePasswordHandler,
        private readonly ChangePhoneHandler     $changePhoneHandler,
        private readonly CheckPhoneHandler      $checkPhoneHandler,
        private readonly ConfirmOtpHandler      $confirmOtpHandler,
        private readonly RegisterHandler        $registerHandler,
        private readonly PasswordLoginHandler   $passwordLoginHandler,
        private readonly ResetPasswordHandler   $resetPasswordHandler,
    ) {}

    /** Raqam ro'yxatdan o'tganmi va paroli bormi — keyingi bosqichni tanlash uchun */
    public function check(CheckPhoneRequest $request): JsonResponse
    {
        $result = $this->checkPhoneHandler->handle(
            CheckPhoneCommand::fromRequest($request)
        );

        return response()->json(['data' => $result]);
    }

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
            'isNew'   => $isNew,
            'message' => 'Muvaffaqiyatli kirildi',
        ], $statusCode);
    }

    /** OTP kodni tasdiqlash — keyingi bosqichga o'tishdan oldin (foydalanuvchi yaratmaydi) */
    public function confirmOtp(ConfirmOtpRequest $request): JsonResponse
    {
        $this->confirmOtpHandler->handle(
            ConfirmOtpCommand::fromRequest($request)
        );

        return response()->json(['message' => 'Kod tasdiqlandi']);
    }

    /** Yangi foydalanuvchi ro'yxatdan o'tishi (OTP + profil + parol) */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registerHandler->handle(
            RegisterCommand::fromRequest($request)
        );

        return $this->authResponse($result, 201);
    }

    /** Mavjud foydalanuvchi parol bilan kirishi */
    public function login(PasswordLoginRequest $request): JsonResponse
    {
        $result = $this->passwordLoginHandler->handle(
            PasswordLoginCommand::fromRequest($request)
        );

        return $this->authResponse($result, 200);
    }

    /** Parolni tiklash (OTP + yangi parol) */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->resetPasswordHandler->handle(
            ResetPasswordCommand::fromRequest($request)
        );

        return $this->authResponse($result, 200);
    }

    /** Tizimga kirgan foydalanuvchi parolni o'zgartirishi */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->changePasswordHandler->handle(
            (int) auth()->id(),
            ChangePasswordCommand::fromRequest($request)
        );

        return response()->json(['message' => 'Parol yangilandi']);
    }

    /** Tizimga kirgan foydalanuvchi telefon raqamini o'zgartirishi (yangi raqam SMS bilan tasdiqlanadi) */
    public function changePhone(ChangePhoneRequest $request): JsonResponse
    {
        $user = $this->changePhoneHandler->handle(
            (int) auth()->id(),
            ChangePhoneCommand::fromRequest($request)
        );

        return response()->json([
            'data'    => ['user' => $this->formatUser($user)],
            'message' => 'Telefon raqami yangilandi',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Tizimdan chiqildi']);
    }

    private function authResponse(array $result, int $statusCode): JsonResponse
    {
        return response()->json([
            'data'  => [
                'token' => $result['token'],
                'user'  => $this->formatUser($result['user']),
            ],
            'message' => 'Muvaffaqiyatli kirildi',
        ], $statusCode);
    }

    private function formatUser(UserEntity $user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'surname'  => $user->surname,
            'phone'    => $user->phone,
            'region'   => $user->region,
            'district' => $user->district,
            'address'  => $user->address,
            'lat'         => $user->lat,
            'lng'         => $user->lng,
            'has_password' => $user->password !== null && $user->password !== '',
        ];
    }
}
