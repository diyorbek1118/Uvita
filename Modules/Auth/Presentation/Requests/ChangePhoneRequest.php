<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class ChangePhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'code' => ['required', 'string', 'digits:4'],
            // Parol o'rnatilmagan eski foydalanuvchilar uchun ixtiyoriy
            'current_password' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Yangi telefon raqam kiritilishi shart.',
            'phone.regex' => "Telefon raqam +998 va to'g'ri mobil operator kodi bilan bo'lishi kerak (masalan +998901234567).",
            'code.required' => 'Tasdiqlash kodi kiritilishi shart.',
            'code.digits' => "Tasdiqlash kodi 4 ta raqamdan iborat bo'lishi kerak.",
            'current_password.max' => 'Joriy parol juda uzun.',
        ];
    }
}
