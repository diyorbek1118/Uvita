<?php

declare(strict_types=1);

namespace Modules\Rating\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Deal\Domain\Enums\DealStatus;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\Rating\Infrastructure\Persistence\Models\RatingModel;
use Modules\Rating\Presentation\Resources\RatingResource;

final class RatingController extends Controller
{
    /** Bitim yakunlangach baholash */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'stars'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $deal = DealModel::findOrFail((int) $validated['deal_id']);
        $userId = auth()->id();

        if (! in_array($userId, [$deal->seller_id, $deal->buyer_id], true)) {
            abort(403, 'Bu bitim sizga tegishli emas.');
        }

        if ($deal->status !== DealStatus::COMPLETED) {
            abort(422, 'Bitim yakunlanmagan — baholab bo\'lmaydi.');
        }

        $ratedId = $deal->seller_id === $userId ? $deal->buyer_id : $deal->seller_id;

        $exists = RatingModel::where('deal_id', $deal->id)
            ->where('rater_id', $userId)
            ->exists();

        if ($exists) {
            abort(422, 'Siz bu bitimni allaqachon baholagansiz.');
        }

        $rating = RatingModel::create([
            'deal_id'  => $deal->id,
            'rater_id' => $userId,
            'rated_id' => $ratedId,
            'stars'    => (int) $validated['stars'],
            'comment'  => $validated['comment'] ?? null,
        ]);

        return RatingResource::make($rating->load(['rater', 'deal.listing']))
            ->additional(['message' => 'Baholash saqlandi. Rahmat!'])
            ->response()
            ->setStatusCode(201);
    }

    /** Foydalanuvchi haqidagi baholashlar */
    public function forUser(int $userId, Request $request): JsonResponse
    {
        $ratings = RatingModel::with(['rater', 'deal.listing'])
            ->where('rated_id', $userId)
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return RatingResource::collection($ratings)->response();
    }

    // ─── Mahsulot (e'lon) bo'yicha sharhlar ─────────────────────────────────

    /** E'lon bo'yicha barcha sharhlar — hammaga ochiq */
    public function forListing(int $listingId, Request $request): JsonResponse
    {
        $ratings = RatingModel::with(['rater'])
            ->where('listing_id', $listingId)
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return RatingResource::collection($ratings)->response();
    }

    /** Xaridor mahsulotni sotib olgach (bitim yakunlangach) sharh qoldiradi */
    public function storeListing(int $listingId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stars'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $listing = ListingModel::findOrFail($listingId);
        $userId  = (int) auth()->id();

        // Faqat shu mahsulotni sotib olgan (yakunlangan bitimga ega) xaridor sharh yozadi
        $deal = DealModel::where('listing_id', $listingId)
            ->where('buyer_id', $userId)
            ->where('status', DealStatus::COMPLETED)
            ->latest()
            ->first();

        if ($deal === null) {
            abort(403, "Bu mahsulotni sotib olmagansiz — sharh qoldira olmaysiz.");
        }

        $exists = RatingModel::where('listing_id', $listingId)
            ->where('rater_id', $userId)
            ->exists();

        if ($exists) {
            abort(422, "Bu mahsulotga sharh allaqachon yozilgan.");
        }

        $rating = RatingModel::create([
            'deal_id'   => $deal->id,
            'listing_id'=> $listingId,
            'rater_id'  => $userId,
            'rated_id'  => $listing->seller_id,
            'stars'     => (int) $validated['stars'],
            'comment'   => $validated['comment'] ?? null,
        ]);

        return RatingResource::make($rating->load(['rater', 'listing']))
            ->additional(['message' => 'Sharhingiz saqlandi. Rahmat!'])
            ->response()
            ->setStatusCode(201);
    }

    /** Xaridor shu e'lon bo'yicha sharh yozish huquqiga ega ekanini qaytaradi */
    public function reviewStatus(int $listingId): JsonResponse
    {
        $userId = (int) auth()->id();

        $canReview = DealModel::where('listing_id', $listingId)
            ->where('buyer_id', $userId)
            ->where('status', DealStatus::COMPLETED)
            ->exists();

        $my = RatingModel::with('rater')
            ->where('listing_id', $listingId)
            ->where('rater_id', $userId)
            ->first();

        return response()->json([
            'can_review' => $canReview && $my === null,
            'my_review'  => $my ? RatingResource::make($my)->resolve() : null,
        ]);
    }
}
