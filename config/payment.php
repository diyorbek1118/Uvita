<?php

declare(strict_types=1);

$testMode = (bool) env('PAYMENT_TEST_MODE', true);

return [

    'test_mode' => $testMode,

    'payme' => [
        'id'       => env('PAYME_MERCHANT_ID', ''),
        'key'      => env('PAYME_KEY', ''),
        'test_key' => env('PAYME_TEST_KEY', ''),
        'test_url' => 'https://checkout.test.paycom.uz',
        'prod_url' => 'https://checkout.paycom.uz',
        // Test rejimda sandbox checkout, aks holda prod
        'checkout' => $testMode
            ? 'https://checkout.test.paycom.uz'
            : 'https://checkout.paycom.uz',
    ],

    'click' => [
        'service_id'  => env('CLICK_SERVICE_ID', ''),
        'merchant_id' => env('CLICK_MERCHANT_ID', ''),
        'secret_key'  => env('CLICK_SECRET_KEY', ''),
        'test_url'    => 'https://my.click.uz/services/pay',
        'prod_url'    => 'https://my.click.uz/services/pay',
        // Click sandbox ham shu URL — farq test service_id/merchant_id da
        'checkout'    => 'https://my.click.uz/services/pay',
    ],

    'uzum' => [
        'service_id' => env('UZUM_SERVICE_ID', ''),
        'username'   => env('UZUM_USERNAME', ''),
        'password'   => env('UZUM_PASSWORD', ''),
        // DIQQAT: Uzum sandbox URL'ini rasmiy hujjatdan tasdiqlang.
        // Hozircha test/prod bir xil — kerak bo'lsa UZUM_CHECKOUT_URL orqali override qiling.
        'test_url'   => 'https://secure.apelsin.uz/open-services/checkout',
        'prod_url'   => 'https://secure.apelsin.uz/open-services/checkout',
        'checkout'   => env('UZUM_CHECKOUT_URL', 'https://secure.apelsin.uz/open-services/checkout'),
    ],

];
