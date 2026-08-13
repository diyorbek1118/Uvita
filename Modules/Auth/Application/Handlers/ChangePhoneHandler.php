<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\ChangePhoneCommand;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class ChangePhoneHandler
{
    public function __construct(
        private readonly OtpAttemptRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface       $userRepository,
    ) {}

    public function handle(int $userId, ChangePhoneCommand $command): User
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new DomainException("Foydalanuvchi topilmadi.");
        }

        // 1. Parol o'rnatilgan bo'lsa — joriy parolni tekshiramiz (raxam o'zgartirishda kimlikni tasdiqlash)
        if ($user->password !== null && $user->password !== '') {
            if (! Hash::check($command->dto->currentPassword ?? '', $user->password)) {
                throw new DomainException("Joriy parol noto'g'ri.");
            }
        }

        $newPhone = new PhoneNumber($command->dto->phone);

        // 2. Yangi raqam joriy raqam bilan bir xil bo'lmasligi kerak
        if ($newPhone->value === $user->phone) {
            throw new DomainException("Bu sizning joriy raqamingiz. Boshqa raqam kiriting.");
        }

        // 3. Yangi raqam boshqa foydalanuvchida band bo'lmasligi kerak
        $owner = $this->userRepository->findByPhone($newPhone->value);
        if ($owner !== null && $owner->id !== $user->id) {
            throw new DomainException("Bu raqam allaqachon ro'yxatdan o'tgan.");
        }

        // 4. Yangi raqam SMS orqali tasdiqlangan bo'lishi kerak (/auth/otp/confirm orqali)
        $verified = $this->otpRepository->findVerifiedByPhone($newPhone->value);
        if ($verified === null) {
            throw new InvalidOtpException("Yangi raqam SMS orqali tasdiqlanmagan. Avval kodni tasdiqlang.");
        }

        // 5. Raqamni yangilash (parallel so'rovda unique constraint — xatosiz ushlaymiz)
        try {
            return $this->userRepository->save($user->withPhone($newPhone->value));
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000' || str_contains($e->getMessage(), 'users_phone_unique')) {
                throw new DomainException("Bu raqam allaqachon ro'yxatdan o'tgan.");
            }
            throw $e;
        }
    }
}
