<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', Rule::unique('staff', 'email')->ignore($this->route('id'))],
            'role'  => ['required', 'in:manager,courier,admin,super_admin'],
        ];
    }
}
