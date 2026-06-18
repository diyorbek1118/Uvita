<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Enums\OtpTypeEnum;

class VerifyOtpRequest extends FormRequest
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
            'type'  => ['required', 'string', 'in:' . implode(',', array_column(OtpTypeEnum::cases(), 'value'))],
        ];
    }
}