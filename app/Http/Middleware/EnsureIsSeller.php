<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIsSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('sanctum')->check()) {
            return response()->json(['message' => 'Autentifikatsiya talab qilinadi'], 401);
        }

        $user = auth('sanctum')->user();

        if (! $user instanceof Staff || ! in_array($user->role, [StaffRole::SELLER, StaffRole::SUPER_ADMIN], true)) {
            return response()->json(['message' => 'Bu bo‘lim faqat sotuvchilar uchun'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Sotuvchi akkaunti faol emas'], 403);
        }

        return $next($request);
    }
}
