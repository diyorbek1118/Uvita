<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
