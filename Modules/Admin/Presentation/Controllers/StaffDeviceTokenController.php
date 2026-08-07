<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Infrastructure\Persistence\Models\StaffDeviceToken;

/**
 * FCM device token ro'yxatdan o'tkazish (kuryer ilovasidan).
 */
final class StaffDeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        StaffDeviceToken::updateOrCreate(
            [
                'staff_id' => auth('sanctum')->id(),
                'token'    => $validated['token'],
            ],
            [
                'platform' => $validated['platform'] ?? 'android',
            ]
        );

        return response()->json(['message' => "Qurilma ro'yxatdan o'tkazildi"]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:512']]);

        StaffDeviceToken::where('staff_id', auth('sanctum')->id())
            ->where('token', $request->input('token'))
            ->delete();

        return response()->json(['message' => "Qurilma olib tashlandi"]);
    }
}
