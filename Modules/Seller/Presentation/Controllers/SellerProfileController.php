<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Seller\Application\DTOs\UpdateSellerProfileDTO;
use Modules\Seller\Application\Handlers\UpdateSellerProfileHandler;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;
use Modules\Seller\Presentation\Requests\UpdateSellerProfileRequest;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

final class SellerProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $profile = SellerProfileModel::query()->where('seller_id', auth('sanctum')->id())->first();

        return response()->json(['data' => $profile === null ? null : SellerProfileResource::make($profile)->resolve()]);
    }

    public function update(UpdateSellerProfileRequest $request, UpdateSellerProfileHandler $handler): JsonResponse
    {
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
        ));

        return SellerProfileResource::make($profile)
            ->additional(['message' => 'Sotuvchi profili saqlandi va tekshiruvga yuborildi'])
            ->response();
    }
}
