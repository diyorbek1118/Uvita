<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:reschedule,cancel'],
        ];
    }
}
