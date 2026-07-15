<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image'  => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'folder' => ['nullable', 'string', 'in:products,categories'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Rasm tanlanishi shart.',
            'image.image'    => "Fayl rasm bo'lishi kerak.",
            'image.mimes'    => 'Ruxsat etilgan formatlar: JPG, PNG, WEBP.',
            'image.max'      => "Rasm hajmi 5 MB dan oshmasligi kerak.",
        ];
    }
}
