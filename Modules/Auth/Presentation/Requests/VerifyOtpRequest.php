<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+998[0-9]{9}$/'],
            'code'  => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => "Telefon raqam kiritilishi shart.",
            'phone.regex'    => "Telefon raqam +998XXXXXXXXX formatida bo'lishi kerak.",
            'code.required'  => "Tasdiqlash kodi kiritilishi shart.",
            'code.digits'    => "Tasdiqlash kodi 6 ta raqamdan iborat bo'lishi kerak.",
        ];
    }
}
