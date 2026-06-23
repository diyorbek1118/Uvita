<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'address'                => ['required', 'array'],
            'address.region'         => ['required', 'string'],
            'address.district'       => ['required', 'string'],
            'address.street'         => ['required', 'string'],
            'address.house'          => ['required', 'string'],
            'address.landmark'       => ['nullable', 'string'],
            'phone'                  => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'phone_secondary'        => ['nullable', 'string', 'regex:/^\+998\d{9}$/'],
            'delivery_time'          => ['required', 'string'],
            'courier_note'           => ['nullable', 'string', 'max:500'],
            'payment_method'         => ['required', 'string', 'in:payme,click,uzum'],
        ];
    }
}
