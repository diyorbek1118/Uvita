<?php

declare(strict_types=1);

namespace Modules\Listing\Presentation\Controllers;

use App\Shared\Services\Upload\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Deal\Domain\Enums\DealStatus;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Application\Commands\CreateListingCommand;
use Modules\Listing\Application\Commands\DeleteListingCommand;
use Modules\Listing\Application\Commands\UpdateListingCommand;
use Modules\Listing\Application\Handlers\CreateListingHandler;
use Modules\Listing\Application\Handlers\DeleteListingHandler;
use Modules\Listing\Application\Handlers\GetMyListingsHandler;
use Modules\Listing\Application\Handlers\UpdateListingHandler;
use Modules\Listing\Application\Queries\GetMyListingsQuery;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\Listing\Presentation\Requests\CreateListingRequest;
use Modules\Listing\Presentation\Requests\UpdateListingRequest;
use Modules\Listing\Presentation\Resources\ListingResource;

final class ListingController extends Controller
{
    public function __construct(
        private readonly CreateListingHandler $createHandler,
        private readonly UpdateListingHandler $updateHandler,
        private readonly DeleteListingHandler $deleteHandler,
        private readonly GetMyListingsHandler $getMyListingsHandler,
        private readonly ListingRepositoryInterface $listings,
    ) {}

    // ─── Public ──────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $listings = $this->listings->paginateActive([
            'category_id' => $request->input('category_id'),
            'search' => $request->input('search'),
            'region' => $request->input('region'),
            'sort' => $request->input('sort', 'newest'),
        ], (int) $request->input('per_page', 20));

        return ListingResource::collection($listings)->response();
    }

    public function show(int $id): JsonResponse
    {
        $listing = $this->listings->findById($id);

        if ($listing === null) {
            return response()->json(['message' => 'E\'lon topilmadi.'], 404);
        }

        // Faol bo'lmagan e'lonlarni faqat egasi ko'ra oladi.
        // Sotilgan (sold) e'lonlarni esa shu mahsulotni sotib olgan xaridor ham ko'ra oladi —
        // sharh qoldirishi uchun.
        $userId = auth('api')->id();
        $isOwner = $userId !== null && $userId === $listing->sellerId;
        $isBuyer = false;
        if ($listing->status->value === 'sold' && $userId !== null) {
            $isBuyer = DealModel::where('listing_id', $id)
                ->where('buyer_id', $userId)
                ->where('status', DealStatus::COMPLETED->value)
                ->exists();
        }
        if ($listing->status->value !== 'active' && ! $isOwner && ! $isBuyer) {
            return response()->json(['message' => 'E\'lon topilmadi.'], 404);
        }

        // ko'rishlar sonini oshiramiz
        ListingModel::where('id', $id)
            ->increment('views');

        return ListingResource::make(
            ListingModel::with(['seller', 'category'])
                ->withAvg('listingRatings as listing_rating', 'stars')
                ->withCount('listingRatings as listing_rating_count')
                ->withAvg('sellerRatings as seller_rating', 'stars')
                ->withCount('sellerRatings as seller_rating_count')
                ->findOrFail($id)
        )->response();
    }

    // ─── Seller (auth) ───────────────────────────────────────────────────────

    public function store(CreateListingRequest $request): JsonResponse
    {
        $listing = $this->createHandler->handle(
            CreateListingCommand::fromRequest($request, auth()->id())
        );

        return ListingResource::make($listing->load(['seller', 'category']))
            ->additional(['message' => 'E\'lon moderatsiyaga yuborildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $id, UpdateListingRequest $request): JsonResponse
    {
        $listing = $this->updateHandler->handle(
            UpdateListingCommand::fromRequest($id, auth()->id(), $request)
        );

        return ListingResource::make($listing->load(['seller', 'category']))
            ->additional(['message' => 'E\'lon yangilandi'])
            ->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteListingCommand($id, auth()->id()));

        return response()->json(['message' => 'E\'lon o\'chirildi']);
    }

    public function myListings(Request $request): JsonResponse
    {
        $listings = $this->getMyListingsHandler->handle(
            new GetMyListingsQuery(auth()->id(), (int) $request->input('per_page', 20))
        );

        return ListingResource::collection($listings)->response();
    }

    /** E'lon uchun video yuklash — public diskka saqlanadi, URL qaytariladi */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,webm,mov,mkv', 'max:51200'], // 50 MB
        ]);

        $url = app(ImageUploadService::class)->store($request->file('video'), 'videos');

        return response()->json([
            'url' => $url,
            'message' => 'Video yuklandi',
        ]);
    }
}
