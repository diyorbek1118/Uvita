<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'provider' => ['required', 'string', 'in:payme,click,uzum'],
        ];
    }
}
