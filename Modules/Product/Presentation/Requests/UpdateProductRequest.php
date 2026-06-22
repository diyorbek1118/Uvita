<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
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
        $productId = (int) $this->route('product');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['required', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
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
            'stock.required'       => "Miqdor kiritilishi shart.",
            'stock.integer'        => "Miqdor butun son bo'lishi kerak.",
            'stock.min'            => "Miqdor 0 dan kichik bo'lishi mumkin emas.",
            'category_id.required' => "Kategoriya tanlanishi shart.",
            'category_id.exists'   => "Tanlangan kategoriya mavjud emas.",
            'images.*.url'         => "Rasm URL manzili noto'g'ri formatda.",
            'slug.unique'          => "Bu slug allaqachon ishlatilgan.",
        ];
    }
}
