<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

final class AdminSellerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profiles = SellerProfileModel::query()
            ->when($request->filled('verified'), fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return SellerProfileResource::collection($profiles)->response();
    }

    public function verify(int $seller): JsonResponse
    {
        $profile = SellerProfileModel::query()->where('seller_id', $seller)->firstOrFail();
        $profile->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by_id' => auth('sanctum')->id(),
        ]);

        return SellerProfileResource::make($profile->fresh())
            ->additional(['message' => 'Sotuvchi tasdiqlandi'])
            ->response();
    }
}
