<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class PasswordLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'regex:' . PhoneNumber::PATTERN],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => "Telefon raqam kiritilishi shart.",
            'phone.regex'       => "Telefon raqam formati to'g'ri bo'lishi kerak (masalan +998901234567).",
            'password.required' => "Parol kiritilishi shart.",
        ];
    }
}
