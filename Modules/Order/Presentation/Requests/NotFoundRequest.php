<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotFoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
