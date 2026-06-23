<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RemoveItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Mahsulot ID si kiritilishi shart.',
        ];
    }
}
