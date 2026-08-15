<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptCourierBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1', 'max:50'],
            'order_ids.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_ids.required' => 'Kamida bitta buyurtmani tanlang.',
            'order_ids.min' => 'Kamida bitta buyurtmani tanlang.',
            'order_ids.max' => 'Bir safar uchun 50 tagacha buyurtma tanlash mumkin.',
            'order_ids.*.distinct' => 'Bir buyurtma ikki marta tanlangan.',
            'order_ids.*.exists' => 'Tanlangan buyurtmalardan biri topilmadi.',
        ];
    }
}
