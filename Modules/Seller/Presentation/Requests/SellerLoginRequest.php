<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SellerLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^\+998\d{9}$/'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
