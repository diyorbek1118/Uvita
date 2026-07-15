<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;

final class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:' . PhoneNumber::PATTERN],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => "Telefon raqam kiritilishi shart.",
            'phone.regex'    => "Telefon raqam +998 va to'g'ri mobil operator kodi bilan bo'lishi kerak (masalan +998901234567).",
        ];
    }
}
