<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email', 'unique:staff,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:manager,courier,admin,super_admin'],
        ];
    }
}
