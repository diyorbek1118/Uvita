<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCourierProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^\+998\d{9}$/'],
            'vehicle_type' => ['nullable', 'string', 'in:foot,bicycle,motorcycle,car'],
            'vehicle_number' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
