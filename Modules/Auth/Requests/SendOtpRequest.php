<?php

namespace Modules\Auth\Requests;

use App\Http\Requests\BaseRequest;
use Modules\Auth\Enums\OtpTypeEnum;

class SendOtpRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+998[0-9]{9}$/'],
            'type'  => ['required', 'string', 'in:' . implode(',', array_column(OtpTypeEnum::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Telefon raqami +998XXXXXXXXX formatida bo\'lishi kerak.',
        ];
    }
}