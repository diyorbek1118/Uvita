<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Parol o'rnatilmagan eski foydalanuvchilar uchun current_password ixtiyoriy
            'current_password' => ['nullable', 'string', 'max:100'],
            'new_password' => ['required', 'string', 'min:6', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.required' => 'Yangi parol kiritilishi shart.',
            'new_password.min' => "Parol kamida 6 ta belgidan iborat bo'lishi kerak.",
        ];
    }
}
