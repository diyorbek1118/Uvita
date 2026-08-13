<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Symfony\Component\HttpFoundation\Response;

final class EnsureVerifiedSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $seller = auth('sanctum')->user();

        if ($seller instanceof Staff && $seller->role === StaffRole::SUPER_ADMIN) {
            return $next($request);
        }

        if (! $seller instanceof Staff || ! SellerProfileModel::query()
            ->where('seller_id', $seller->id)
            ->where('is_verified', true)
            ->exists()) {
            return response()->json(['message' => 'Mahsulot joylash uchun sotuvchi profili admin tomonidan tasdiqlanishi kerak'], 403);
        }

        return $next($request);
    }
}
