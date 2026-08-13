<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateCourierPayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'courier_id' => [
                'required', 'integer',
                Rule::exists('staff', 'id')->where(fn ($query) => $query->where('role', 'courier')),
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
