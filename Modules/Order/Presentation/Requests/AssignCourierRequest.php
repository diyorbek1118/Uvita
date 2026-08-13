<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCourierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'courier_id' => [
                'required',
                'integer',
                Rule::exists('staff', 'id')->where(
                    fn ($query) => $query->where('role', 'courier')->where('is_active', true)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'courier_id.required' => 'Kuryer tanlanishi shart.',
            'courier_id.exists' => 'Faqat faol kuryer tanlanishi mumkin.',
        ];
    }
}
