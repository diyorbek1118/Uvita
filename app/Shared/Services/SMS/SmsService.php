<?php

declare(strict_types=1);

namespace App\Shared\Services\SMS;

use Illuminate\Support\Facades\Log;

class SmsService
{
    // TODO: Replace mock with real provider (Eskiz, PlayMobile, etc.)
    public function send(string $phone, string $message): void
    {
        if (! app()->environment('production')) {
            Log::info('[SMS MOCK]', [
                'phone'   => $phone,
                'message' => $message,
            ]);

            return;
        }

        // TODO: integrate real SMS provider here
        // $this->sendViaEskiz($phone, $message);
    }
}
