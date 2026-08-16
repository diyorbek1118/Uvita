<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlanCourierTripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'route_key' => ['required', 'string', 'max:255'],
            'capacity_kg' => ['nullable', 'numeric', 'min:1', 'max:50000'],
        ];
    }
}
