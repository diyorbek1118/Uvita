<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SetCourierAvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return ['is_online' => ['required', 'boolean']];
    }
}
