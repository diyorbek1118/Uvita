<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Seller\Application\Services\SellerShopResolver;
use Symfony\Component\HttpFoundation\Response;

final class EnsureVerifiedSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $seller = auth('sanctum')->user();

        if ($seller instanceof Staff && $seller->role === StaffRole::SUPER_ADMIN) {
            return $next($request);
        }

        if (! $seller instanceof Staff) {
            return response()->json(['message' => 'Seller akkaunti topilmadi'], 403);
        }

        $shop = app(SellerShopResolver::class)->resolve($request, $seller->id);
        if (! $shop->is_verified) {
            return response()->json(['message' => 'Mahsulot joylash uchun sotuvchi profili admin tomonidan tasdiqlanishi kerak'], 403);
        }

        return $next($request);
    }
}
