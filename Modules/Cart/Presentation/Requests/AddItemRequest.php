<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Mahsulot ID si kiritilishi shart.',
            'product_id.exists'   => 'Bunday mahsulot topilmadi.',
            'quantity.required'   => 'Miqdor kiritilishi shart.',
            'quantity.min'        => 'Miqdor kamida 1 bo\'lishi kerak.',
            'quantity.max'        => 'Miqdor 100 tadan oshmasligi kerak.',
        ];
    }
}
