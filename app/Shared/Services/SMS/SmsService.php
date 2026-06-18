<?php

namespace App\Shared\Services\SMS;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS to the given phone number.
     * TODO: Replace mock with real provider (Eskiz, PlayMobile, etc.)
     */
    public function send(string $phone, string $message): bool
    {
        if (app()->environment('production')) {
            // TODO: integrate real SMS provider here
            // return $this->sendViaEskiz($phone, $message);
        }

        // Mock: just log the message in non-production
        Log::info('[SMS MOCK]', [
            'phone'   => $phone,
            'message' => $message,
        ]);

        return true;
    }
}