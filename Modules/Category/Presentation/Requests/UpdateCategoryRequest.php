<?php

declare(strict_types=1);

namespace Modules\Category\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('category');

        return [
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:255', "unique:categories,slug,{$id}", 'regex:/^[a-z0-9-]+$/'],
            'image'     => ['nullable', 'url', 'max:500'],
            'parent_id' => ['nullable', 'integer', "exists:categories,id"],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => "Kategoriya nomi kiritilishi shart.",
            'slug.unique'      => "Bu slug allaqachon mavjud.",
            'slug.regex'       => "Slug faqat kichik harflar, raqamlar va tire (-) dan iborat bo'lishi kerak.",
            'parent_id.exists' => "Tanlangan ota kategoriya mavjud emas.",
        ];
    }
}
