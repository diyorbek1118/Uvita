<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCourierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'courier_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
