<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\ChangePasswordCommand;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(int $userId, ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new DomainException("Foydalanuvchi topilmadi.");
        }

        // Parol o'rnatilgan bo'lsa — joriy parolni tekshiramiz
        if ($user->password !== null && $user->password !== '') {
            if (! Hash::check($command->dto->currentPassword ?? '', $user->password)) {
                throw new DomainException("Joriy parol noto'g'ri.");
            }
        }

        $this->userRepository->save(
            $user->withPassword(Hash::make($command->dto->newPassword))
        );
    }
}
