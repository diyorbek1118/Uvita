<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

final class CreateSellerProductRequest extends FormRequest
{
    public function rules(): array
    {
        $minImages = (int) config('seller.media.min_images', 4);
        $maxImages = (int) config('seller.media.max_images', 10);
        $imageMax = (int) config('seller.media.image_max_kb', 5120);
        $minWidth = (int) config('seller.media.image_min_width', 800);
        $minHeight = (int) config('seller.media.image_min_height', 800);
        $videoMax = (int) config('seller.media.video_max_kb', 51200);

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000'],
            'stock' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'origin_region' => ['required', 'string', 'max:150'],
            'farmer_name' => ['required', 'string', 'max:150'],
            'unit' => ['required', Rule::in(['kg', 'tonna', 'dona', 'quti', 'bog‘'])],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'images' => ['required', 'array', "min:$minImages", "max:$maxImages"],
            'images.*' => [
                'required',
                File::image()->types(['jpeg', 'jpg', 'png', 'webp'])->max($imageMax),
                "dimensions:min_width=$minWidth,min_height=$minHeight",
            ],
            'primary_image_index' => ['required', 'integer', 'min:0', 'lt:'.($maxImages + 1)],
            'video' => ['required', File::types(['mp4', 'mov', 'webm'])->max($videoMax)],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.min' => 'Kamida 4 ta haqiqiy mahsulot rasmi yuklang.',
            'images.*.dimensions' => 'Har bir rasm kamida 800×800 piksel bo‘lishi kerak.',
            'video.required' => 'Kamida bitta real mahsulot videosi majburiy.',
            'terms_accepted.accepted' => 'Komissiyalar va moderatsiya shartlariga rozilik bildiring.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $images = $this->file('images', []);
            $primary = (int) $this->input('primary_image_index', -1);

            if ($primary < 0 || $primary >= count($images)) {
                $validator->errors()->add('primary_image_index', 'Asosiy rasm mavjud rasmlardan biri bo‘lishi kerak.');
            }
        }];
    }
}
