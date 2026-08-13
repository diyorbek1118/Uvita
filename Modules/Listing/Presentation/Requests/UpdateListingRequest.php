<?php

declare(strict_types=1);

namespace Modules\Listing\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'title'       => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price'       => ['required', 'integer', 'min:1'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'unit'        => ['required', "in:kg,tonna,dona,litr,bog'lam,quti,o'ram,sumka,dasta"],
            'images'      => ['nullable', 'array', 'max:5'],
            'images.*'    => ['string', 'max:5000000'],
            'video'       => ['nullable', 'string', 'max:500'],
            'region'      => ['nullable', 'string', 'max:100'],
            'district'    => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:300'],
            'lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'lng'         => ['nullable', 'numeric', 'between:-180,180'],
            'details'     => ['nullable', 'array'],
            'contacts'    => ['nullable', 'array', 'max:10'],
            'contacts.*.type'  => ['required_with:contacts', 'string', 'in:telefon,telegram,instagram,youtube,website,other'],
            'contacts.*.value' => ['required_with:contacts', 'string', 'max:200'],
        ];
    }
}
