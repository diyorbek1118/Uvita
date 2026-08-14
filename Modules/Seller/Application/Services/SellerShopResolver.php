<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Services;

use Illuminate\Http\Request;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class SellerShopResolver
{
    public function resolve(Request $request, int $sellerId): SellerProfileModel
    {
        $shopId = $request->header('X-Seller-Shop-Id') ?? $request->input('seller_shop_id');

        $query = SellerProfileModel::query()
            ->where('seller_id', $sellerId)
            ->where('is_active', true);

        if (is_numeric($shopId)) {
            $query->whereKey((int) $shopId);
        }

        return $query->oldest('id')->firstOrFail();
    }

    public function resolveOptional(Request $request, int $sellerId): ?SellerProfileModel
    {
        try {
            return $this->resolve($request, $sellerId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return null;
        }
    }
}
