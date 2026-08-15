<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSellerShopRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:2', 'max:255'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }
}
