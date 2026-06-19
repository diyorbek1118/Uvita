<?php

namespace Modules\Auth\Requests;

use App\Http\Requests\BaseRequest;
use Modules\Auth\Enums\OtpTypeEnum;

class VerifyOtpRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+998[0-9]{9}$/'],
            'code'  => ['required', 'string', 'digits:6'],
            'type'  => ['required', 'string', 'in:' . implode(',', array_column(OtpTypeEnum::cases(), 'value'))],
        ];
    }
}