<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('sanctum')->check()) {
            return response()->json(['message' => 'Autentifikatsiya talab qilinadi'], 401);
        }

        $user = auth('sanctum')->user();

        if (!($user instanceof Staff) || ($user->role !== StaffRole::MANAGER && $user->role !== StaffRole::SUPER_ADMIN)) {
            return response()->json(['message' => 'Bu sahifaga faqat menejerlar kirishi mumkin'], 403);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akkauntingiz faol emas. Adminga murojaat qiling'], 403);
        }

        return $next($request);
    }
}
