<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterCourierDeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:web,android,ios'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
