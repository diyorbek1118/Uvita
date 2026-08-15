<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Controllers;

use App\Shared\Services\Upload\ProductMediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Application\DTOs\SellerProductDraftDTO;
use Modules\Product\Application\Handlers\SaveSellerProductDraftHandler;
use Modules\Product\Domain\Services\ProductFeeCalculator;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Product\Presentation\Requests\CreateSellerProductRequest;
use Modules\Product\Presentation\Resources\ProductRevisionResource;
use Modules\Seller\Application\Services\SellerShopResolver;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class SellerProductController extends Controller
{
    public function pricing(Request $request, ProductFeeCalculator $calculator): JsonResponse
    {
        $validated = $request->validate(['price' => ['required', 'integer', 'min:1000']]);

        return response()->json(['data' => $calculator->calculate((int) $validated['price'])->toArray()]);
    }

    public function index(Request $request): JsonResponse
    {
        $sellerId = $this->seller()->id;
        $shopId = $this->shop($request)->id;
        $products = Product::query()
            ->where('seller_id', $sellerId)
            ->where(fn ($query) => $query->where('seller_profile_id', $shopId)->orWhereNull('seller_profile_id'))
            ->with(['category', 'revisions' => fn ($query) => $query->latest('version')->limit(1)])
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($products);
    }

    public function analytics(Request $request): JsonResponse
    {
        $sellerId = $this->seller()->id;
        $shopId = $this->shop($request)->id;
        $productIds = Product::query()->where('seller_id', $sellerId)
            ->where(fn ($query) => $query->where('seller_profile_id', $shopId)->orWhereNull('seller_profile_id'))
            ->pluck('id');
        $soldStatuses = ['paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered'];
        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereIn('orders.status', $soldStatuses)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as units, COALESCE(SUM(order_items.quantity * order_items.price), 0) as revenue')
            ->first();

        $revenue = (int) ($sales?->revenue ?? 0);
        $netRate = 100 - array_sum(config('seller.fees'));

        return response()->json(['data' => [
            'products_count' => $productIds->count(),
            'active_products_count' => Product::query()->whereIn('id', $productIds)->where('status', 'active')->count(),
            'pending_revisions_count' => Product::query()->whereIn('id', $productIds)->whereHas('revisions', fn ($q) => $q->where('status', 'pending'))->count(),
            'units_sold' => (int) ($sales?->units ?? 0),
            'gross_revenue' => $revenue,
            'estimated_net_revenue' => (int) round($revenue * $netRate / 100),
        ]]);
    }

    public function store(CreateSellerProductRequest $request, ProductMediaUploadService $media, SaveSellerProductDraftHandler $handler): JsonResponse
    {
        $revision = $handler->handle($this->dto($request, $media), $this->seller()->id, null, $this->shop($request)->id);

        return ProductRevisionResource::make($revision)
            ->additional(['message' => 'Mahsulot moderatsiyaga yuborildi'])
            ->response()->setStatusCode(201);
    }

    public function update(int $product, CreateSellerProductRequest $request, ProductMediaUploadService $media, SaveSellerProductDraftHandler $handler): JsonResponse
    {
        $revision = $handler->handle($this->dto($request, $media), $this->seller()->id, $product, $this->shop($request)->id);

        return ProductRevisionResource::make($revision)
            ->additional(['message' => 'Tahrir moderatsiyaga yuborildi; marketda avvalgi tasdiqlangan versiya qoladi'])
            ->response()->setStatusCode(201);
    }

    private function dto(CreateSellerProductRequest $request, ProductMediaUploadService $media): SellerProductDraftDTO
    {
        return new SellerProductDraftDTO(
            name: (string) $request->validated('name'),
            slug: Str::slug((string) $request->validated('name')),
            description: (string) $request->validated('description'),
            price: (int) $request->validated('price'),
            stock: (int) $request->validated('stock'),
            categoryId: (int) $request->validated('category_id'),
            images: $media->storeImages($request->file('images')),
            primaryImageIndex: (int) $request->validated('primary_image_index'),
            videoUrl: $media->storeVideo($request->file('video')),
            originRegion: (string) $request->validated('origin_region'),
            farmerName: (string) $request->validated('farmer_name'),
            unit: (string) $request->validated('unit'),
            minimumOrderQuantity: (int) $request->validated('minimum_order_quantity'),
            termsAccepted: true,
        );
    }

    private function seller(): Staff
    {
        /** @var Staff $seller */
        $seller = auth('sanctum')->user();

        return $seller;
    }

    private function shop(Request $request): SellerProfileModel
    {
        return app(SellerShopResolver::class)->resolve($request, $this->seller()->id);
    }
}
