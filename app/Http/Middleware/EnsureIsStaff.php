<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Symfony\Component\HttpFoundation\Response;

/**
 * Birlashgan web panel guard: manager | admin | super_admin.
 * Courier bu yerga kirmaydi (u alohida mobil ilova bilan ishlaydi).
 * Nozik ruxsatlar (o'chirish, moliyaviy) handler ichida rol bo'yicha tekshiriladi.
 */
final class EnsureIsStaff
{
    private const ALLOWED = [
        StaffRole::MANAGER,
        StaffRole::ADMIN,
        StaffRole::SUPER_ADMIN,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('sanctum')->check()) {
            return response()->json(['message' => 'Autentifikatsiya talab qilinadi'], 401);
        }

        $user = auth('sanctum')->user();

        if (!($user instanceof Staff) || !in_array($user->role, self::ALLOWED, true)) {
            return response()->json(['message' => 'Bu sahifaga faqat xodimlar kirishi mumkin'], 403);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akkauntingiz faol emas. Adminga murojaat qiling'], 403);
        }

        return $next($request);
    }
}
