<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSellerProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $shopId = $this->header('X-Seller-Shop-Id');

        return [
            'business_name' => ['required', 'string', 'min:2', 'max:255'],
            'legal_type' => ['required', 'in:individual,farmer_farm,company'],
            'tin' => ['required', 'digits_between:9,14', Rule::unique('seller_profiles', 'tin')->ignore(is_numeric($shopId) ? (int) $shopId : null)],
            'phone' => ['required', 'regex:/^\+998\d{9}$/'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'bank_account' => ['required', 'digits_between:20,32'],
            'bank_mfo' => ['required', 'digits:5'],
            'terms_accepted' => ['accepted'],
            'pickup_latitude' => ['nullable', 'required_with:pickup_longitude', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'required_with:pickup_latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
