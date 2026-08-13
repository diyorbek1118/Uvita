<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteDeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pin' => ['nullable', 'digits:4'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
