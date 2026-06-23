<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
