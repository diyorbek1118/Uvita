<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePendingOrderItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Buyurtmada kamida bitta mahsulot bo‘lishi kerak.',
            'items.min' => 'Buyurtmada kamida bitta mahsulot bo‘lishi kerak.',
            'items.*.product_id.distinct' => 'Bir mahsulotni ikki marta kiritib bo‘lmaydi.',
            'items.*.quantity.min' => 'Mahsulot miqdori kamida 1 bo‘lishi kerak.',
        ];
    }
}
