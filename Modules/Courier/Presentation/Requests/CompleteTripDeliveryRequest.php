<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteTripDeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pin' => ['required', 'digits:4'],
            'cash_received' => ['required', 'integer', 'min:0'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
