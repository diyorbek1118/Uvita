<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            // Kod qiymati bu yerda tekshirilmaydi — muhimi OTP /otp/confirm orqali tasdiqlangan bo'lishi.
            'code' => ['nullable', 'string', 'digits:4'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Telefon raqam kiritilishi shart.',
            'phone.regex' => "Telefon raqam formati to'g'ri bo'lishi kerak (masalan +998901234567).",
            'code.required' => 'Tasdiqlash kodi kiritilishi shart.',
            'code.digits' => "Tasdiqlash kodi 4 ta raqamdan iborat bo'lishi kerak.",
            'password.required' => 'Yangi parol kiritilishi shart.',
            'password.min' => "Parol kamida 6 ta belgidan iborat bo'lishi kerak.",
        ];
    }
}
