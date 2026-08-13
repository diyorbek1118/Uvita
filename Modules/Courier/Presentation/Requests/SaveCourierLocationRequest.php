<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCourierLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
