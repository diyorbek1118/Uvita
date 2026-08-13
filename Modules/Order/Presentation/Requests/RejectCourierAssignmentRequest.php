<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectCourierAssignmentRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }
}
