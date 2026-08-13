<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\PasswordLoginCommand;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class PasswordLoginHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenServiceInterface   $tokenService,
    ) {}

    /**
     * @return array{token: string, user: UserEntity}
     */
    public function handle(PasswordLoginCommand $command): array
    {
        $phone = new PhoneNumber($command->dto->phone);
        $user  = $this->userRepository->findByPhone($phone->value);

        // 1. Foydalanuvchi mavjud va paroli o'rnatilgan bo'lishi kerak
        if ($user === null || $user->password === null || $user->password === '') {
            throw new DomainException("Bu raqam uchun parol o'rnatilmagan. Parolni tiklash orqali o'rnating.");
        }

        // 2. Parolni tekshirish
        if (! Hash::check($command->dto->password, $user->password)) {
            throw new DomainException("Telefon raqam yoki parol noto'g'ri.");
        }

        // 3. Token generatsiya
        $token = $this->tokenService->createForUser($user);

        return ['token' => $token, 'user' => $user];
    }
}
