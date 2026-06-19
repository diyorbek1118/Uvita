<?php

namespace Modules\User\Requests;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ism kiritilishi shart.',
            'name.max'      => 'Ism 255 belgidan oshmasligi kerak.',
        ];
    }
}