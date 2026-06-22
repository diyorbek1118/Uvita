<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->input('name', ''))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['required', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['string', 'url'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => "Mahsulot nomi kiritilishi shart.",
            'description.required' => "Tavsif kiritilishi shart.",
            'price.required'       => "Narx kiritilishi shart.",
            'price.integer'        => "Narx butun son bo'lishi kerak.",
            'price.min'            => "Narx 0 dan kichik bo'lishi mumkin emas.",
            'stock.integer'        => "Miqdor butun son bo'lishi kerak.",
            'stock.min'            => "Miqdor 0 dan kichik bo'lishi mumkin emas.",
            'category_id.required' => "Kategoriya tanlanishi shart.",
            'category_id.exists'   => "Tanlangan kategoriya mavjud emas.",
            'images.*.url'         => "Rasm URL manzili noto'g'ri formatda.",
            'slug.unique'          => "Bu slug allaqachon ishlatilgan.",
        ];
    }
}
