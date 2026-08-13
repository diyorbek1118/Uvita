<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Courier\Domain\Enums\DeliveryAttemptReason;

class NotFoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason_code' => ['nullable', 'required_without:reason', Rule::enum(DeliveryAttemptReason::class)],
            'reason' => ['nullable', 'required_without:reason_code', 'string', 'max:500'],
            'reason_note' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
