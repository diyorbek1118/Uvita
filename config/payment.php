<?php

declare(strict_types=1);

return [

    'test_mode' => (bool) env('PAYMENT_TEST_MODE', true),

    'payme' => [
        'id'       => env('PAYME_MERCHANT_ID', ''),
        'key'      => env('PAYME_KEY', ''),
        'test_key' => env('PAYME_TEST_KEY', ''),
        'test_url' => 'https://checkout.test.paycom.uz',
        'prod_url' => 'https://checkout.paycom.uz',
    ],

    'click' => [
        'service_id'  => env('CLICK_SERVICE_ID', ''),
        'merchant_id' => env('CLICK_MERCHANT_ID', ''),
        'secret_key'  => env('CLICK_SECRET_KEY', ''),
        'test_url'    => 'https://my.click.uz/services/pay',
        'prod_url'    => 'https://my.click.uz/services/pay',
    ],

    'uzum' => [
        'service_id' => env('UZUM_SERVICE_ID', ''),
        'username'   => env('UZUM_USERNAME', ''),
        'password'   => env('UZUM_PASSWORD', ''),
        'test_url'   => 'https://secure.apelsin.uz/open-services/checkout',
        'prod_url'   => 'https://secure.apelsin.uz/open-services/checkout',
    ],

];
