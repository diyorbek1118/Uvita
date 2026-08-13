<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResolveSupportTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:in_progress,resolved'],
            'reply' => ['nullable', 'required_if:status,resolved', 'string', 'max:2000'],
        ];
    }
}
