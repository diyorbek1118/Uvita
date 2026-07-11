<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\ValueObjects;

enum SettingKey: string
{
    case DELIVERY_CITY          = 'delivery_city';
    case MIN_ORDER_AMOUNT       = 'min_order_amount';
    case OTP_EXPIRY_SECONDS     = 'otp_expiry_seconds';
    case OTP_MAX_ATTEMPTS       = 'otp_max_attempts';
    case OTP_BLOCK_MINUTES      = 'otp_block_minutes';
    case MAX_NOT_FOUND_ATTEMPTS = 'max_not_found_attempts';
    case REVIEW_REQUEST_DELAY   = 'review_request_delay_hours';
}
