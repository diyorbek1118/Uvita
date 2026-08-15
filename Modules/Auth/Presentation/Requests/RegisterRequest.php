<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class RegisterRequest extends FormRequest
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
            // Refresh'dan keyin bo'sh bo'lsa ham register ishlaydi (tasdiqlash serverda saqlangan).
            'code' => ['nullable', 'string', 'digits:4'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'surname' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:300'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
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
            'name.required' => 'Ismingizni kiriting.',
            'name.min' => "Ism kamida 2 ta belgidan iborat bo'lishi kerak.",
            'password.required' => 'Parol kiritilishi shart.',
            'password.min' => "Parol kamida 6 ta belgidan iborat bo'lishi kerak.",
        ];
    }
}
