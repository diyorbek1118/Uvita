<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use Modules\Auth\Application\Commands\CheckPhoneCommand;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\Repositories\UserRepositoryInterface;

final class CheckPhoneHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{registered: bool, has_password: bool}
     */
    public function handle(CheckPhoneCommand $command): array
    {
        $phone = new PhoneNumber($command->dto->phone);
        $user = $this->userRepository->findByPhone($phone->value);

        return [
            'registered' => $user !== null,
            'has_password' => $user !== null && $user->password !== null && $user->password !== '',
        ];
    }
}
