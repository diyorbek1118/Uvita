<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Admin\Presentation\Resources\StaffResource;
use Modules\Seller\Application\Handlers\CreateSellerHandler;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Modules\Seller\Presentation\Requests\CreateSellerRequest;
use Modules\Seller\Presentation\Requests\CreateSellerShopRequest;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

final class AdminSellerController extends Controller
{
    public function sellers(Request $request): JsonResponse
    {
        $sellers = Staff::query()
            ->where('role', StaffRole::SELLER)
            ->with('sellerProfiles')
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return StaffResource::collection($sellers)->response();
    }

    public function store(CreateSellerRequest $request, CreateSellerHandler $handler): JsonResponse
    {
        $seller = $handler->handle($request->validated());

        return StaffResource::make($seller)
            ->additional(['message' => 'Seller va birinchi do‘kon yaratildi'])
            ->response()->setStatusCode(201);
    }

    public function addShop(int $seller, CreateSellerShopRequest $request, CreateSellerHandler $handler): JsonResponse
    {
        Staff::query()->where('role', StaffRole::SELLER)->findOrFail($seller);
        $shop = $handler->createShop($seller, $request->validated());

        return SellerProfileResource::make($shop)
            ->additional(['message' => 'Yangi do‘kon qo‘shildi'])
            ->response()->setStatusCode(201);
    }

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

    public function verifyShop(int $shop): JsonResponse
    {
        $profile = SellerProfileModel::query()->findOrFail($shop);
        $profile->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by_id' => auth('sanctum')->id(),
        ]);

        return SellerProfileResource::make($profile->fresh())
            ->additional(['message' => 'Do‘kon tasdiqlandi'])
            ->response();
    }
}
