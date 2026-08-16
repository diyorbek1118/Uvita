<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Seller\Application\DTOs\UpdateSellerProfileDTO;
use Modules\Seller\Application\Handlers\UpdateSellerProfileHandler;
use Modules\Seller\Application\Services\SellerShopResolver;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Modules\Seller\Presentation\Requests\UpdateSellerProfileRequest;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

final class SellerProfileController extends Controller
{
    public function show(Request $request, SellerShopResolver $resolver): JsonResponse
    {
        $profile = $resolver->resolve($request, (int) auth('sanctum')->id());

        return response()->json(['data' => SellerProfileResource::make($profile)->resolve()]);
    }

    public function shops(): JsonResponse
    {
        return SellerProfileResource::collection(
            SellerProfileModel::query()->where('seller_id', auth('sanctum')->id())->where('is_active', true)->oldest()->get()
        )->response();
    }

    public function update(UpdateSellerProfileRequest $request, UpdateSellerProfileHandler $handler, SellerShopResolver $resolver): JsonResponse
    {
        $shop = $resolver->resolve($request, (int) auth('sanctum')->id());
        $profile = $handler->handle((int) auth('sanctum')->id(), new UpdateSellerProfileDTO(
            businessName: $request->validated('business_name'),
            legalType: $request->validated('legal_type'),
            tin: $request->validated('tin'),
            phone: $request->validated('phone'),
            region: $request->validated('region'),
            district: $request->validated('district'),
            address: $request->validated('address'),
            bankAccount: $request->validated('bank_account'),
            bankMfo: $request->validated('bank_mfo'),
            termsAccepted: true,
            pickupLatitude: $request->filled('pickup_latitude') ? $request->float('pickup_latitude') : null,
            pickupLongitude: $request->filled('pickup_longitude') ? $request->float('pickup_longitude') : null,
        ), $shop->id);

        return SellerProfileResource::make($profile)
            ->additional(['message' => 'Sotuvchi profili saqlandi va tekshiruvga yuborildi'])
            ->response();
    }
}
