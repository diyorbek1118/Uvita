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
        $maxWidth = (int) config('seller.media.image_max_width', 3000);
        $maxHeight = (int) config('seller.media.image_max_height', 3000);
        $imageRatio = (float) config('seller.media.image_ratio', 1);
        $videoMax = (int) config('seller.media.video_max_kb', 5120);

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000'],
            'stock' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'origin_region' => ['required', 'string', 'max:150'],
            'farmer_name' => ['required', 'string', 'max:150'],
            'unit' => ['required', Rule::in(['kg', 'tonna', 'dona', 'litr', 'quti', 'bog‘'])],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'images' => ['required', 'array', "min:$minImages", "max:$maxImages"],
            'images.*' => [
                'required',
                File::image()->types(['jpeg', 'jpg', 'png', 'webp'])->max($imageMax),
                "dimensions:min_width=$minWidth,min_height=$minHeight,max_width=$maxWidth,max_height=$maxHeight,ratio=$imageRatio",
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
            'images.max' => 'Ko‘pi bilan 10 ta mahsulot rasmi yuklash mumkin.',
            'images.*.max' => 'Har bir rasm hajmi 5 MB dan oshmasligi kerak.',
            'images.*.dimensions' => 'Har bir rasm kvadrat (1:1) va 800×800 dan 3000×3000 pikselgacha bo‘lishi kerak.',
            'video.required' => 'Kamida bitta real mahsulot videosi majburiy.',
            'video.max' => 'Video hajmi 5 MB dan oshmasligi kerak.',
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

            $expectedDimensions = null;
            foreach ($images as $index => $image) {
                $dimensions = @getimagesize($image->getRealPath());
                if ($dimensions === false) {
                    continue;
                }

                $currentDimensions = [(int) $dimensions[0], (int) $dimensions[1]];
                if ($expectedDimensions !== null && $currentDimensions !== $expectedDimensions) {
                    $validator->errors()->add("images.$index", 'Barcha rasmlarning piksel o‘lchami bir xil bo‘lishi kerak.');
                }
                $expectedDimensions ??= $currentDimensions;
            }
        }];
    }
}
