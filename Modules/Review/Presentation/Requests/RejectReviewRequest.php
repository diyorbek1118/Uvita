<?php

declare(strict_types=1);

namespace Modules\Review\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
