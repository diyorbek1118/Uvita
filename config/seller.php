<?php

declare(strict_types=1);

return [
    'fees' => [
        'platform_percent' => (float) env('SELLER_PLATFORM_FEE_PERCENT', 10),
        'courier_percent' => (float) env('SELLER_COURIER_FEE_PERCENT', 5),
        'tax_percent' => (float) env('SELLER_TAX_PERCENT', 1),
        'payment_percent' => (float) env('SELLER_PAYMENT_FEE_PERCENT', 3),
    ],
    'media' => [
        'min_images' => 4,
        'max_images' => 10,
        'image_max_kb' => 5120,
        'image_min_width' => 800,
        'image_min_height' => 800,
        'video_max_kb' => 51200,
    ],
];
