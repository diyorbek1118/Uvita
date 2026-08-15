<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class ConfirmOtpRequest extends FormRequest
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
            'purpose' => ['required', 'string', Rule::in(['register', 'reset', 'change_phone'])],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Telefon raqam kiritilishi shart.',
            'phone.regex' => "Telefon raqam formati to'g'ri bo'lishi kerak (masalan +998901234567).",
            'code.required' => 'Tasdiqlash kodi kiritilishi shart.',
            'code.digits' => "Tasdiqlash kodi 4 ta raqamdan iborat bo'lishi kerak.",
            'purpose.required' => "So'rov maqsadi kiritilishi shart.",
            'purpose.in' => "So'rov maqsadi noto'g'ri.",
        ];
    }
}
