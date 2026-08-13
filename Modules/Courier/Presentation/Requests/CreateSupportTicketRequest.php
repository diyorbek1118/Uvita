<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSupportTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer'],
            'category' => ['required', 'string', 'in:delivery,customer,vehicle,accident,app,other'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
