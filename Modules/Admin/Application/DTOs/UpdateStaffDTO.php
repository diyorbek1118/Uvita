<?php

declare(strict_types=1);

namespace Modules\Admin\Application\DTOs;

use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;

final readonly class UpdateStaffDTO
{
    public function __construct(
        public string    $name,
        public string    $email,
        public StaffRole $role,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:  $request->input('name'),
            email: $request->input('email'),
            role:  StaffRole::from($request->input('role')),
        );
    }
}
