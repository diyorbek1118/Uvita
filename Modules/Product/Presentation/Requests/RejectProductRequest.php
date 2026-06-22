<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectProductRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'reason.required' => "Rad etish sababi kiritilishi shart.",
            'reason.max'      => "Sabab 500 ta belgidan oshmasligi kerak.",
        ];
    }
}
