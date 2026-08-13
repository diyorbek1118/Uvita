<?php

declare(strict_types=1);

namespace Modules\Deal\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'quantity'   => ['required', 'numeric', 'min:0.1'],
            'unit'       => ['required', "in:kg,tonna,dona,litr,bog'lam,quti,o'ram,sumka,dasta"],
        ];
    }
}
