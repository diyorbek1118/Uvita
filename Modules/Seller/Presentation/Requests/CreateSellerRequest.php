<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSellerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:/^\+998\d{9}$/', 'unique:staff,phone'],
            'password' => ['required', 'string', 'min:8'],
            'business_name' => ['required', 'string', 'min:2', 'max:255'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }
}
