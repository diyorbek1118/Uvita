<?php

declare(strict_types=1);

namespace Modules\Review\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Review\Application\Commands\ApproveReviewCommand;
use Modules\Review\Application\Commands\CreateReviewCommand;
use Modules\Review\Application\Commands\RejectReviewCommand;
use Modules\Review\Application\Commands\UpdateReviewCommand;
use Modules\Review\Application\DTOs\UpdateReviewDTO;
use Modules\Review\Application\Handlers\ApproveReviewHandler;
use Modules\Review\Application\Handlers\CreateReviewHandler;
use Modules\Review\Application\Handlers\GetMyReviewsHandler;
use Modules\Review\Application\Handlers\GetPendingReviewsHandler;
use Modules\Review\Application\Handlers\GetProductReviewsHandler;
use Modules\Review\Application\Handlers\RejectReviewHandler;
use Modules\Review\Application\Handlers\UpdateReviewHandler;
use Modules\Review\Application\Queries\GetMyReviewsQuery;
use Modules\Review\Application\Queries\GetPendingReviewsQuery;
use Modules\Review\Application\Queries\GetProductReviewsQuery;
use Modules\Review\Presentation\Requests\CreateReviewRequest;
use Modules\Review\Presentation\Requests\RejectReviewRequest;
use Modules\Review\Presentation\Requests\UpdateReviewRequest;
use Modules\Review\Presentation\Resources\ReviewResource;

final class ReviewController extends Controller
{
    public function __construct(
        private readonly CreateReviewHandler      $createHandler,
        private readonly UpdateReviewHandler      $updateHandler,
        private readonly ApproveReviewHandler     $approveHandler,
        private readonly RejectReviewHandler      $rejectHandler,
        private readonly GetProductReviewsHandler $getProductReviewsHandler,
        private readonly GetPendingReviewsHandler $getPendingReviewsHandler,
        private readonly GetMyReviewsHandler      $getMyReviewsHandler,
    ) {}

    // ─── Public ──────────────────────────────────────────────────────────────

    public function productReviews(int $productId): JsonResponse
    {
        $reviews = $this->getProductReviewsHandler->handle(
            new GetProductReviewsQuery($productId)
        );

        return ReviewResource::collection($reviews)->response();
    }

    // ─── Customer ────────────────────────────────────────────────────────────

    public function store(CreateReviewRequest $request): JsonResponse
    {
        $review = $this->createHandler->handle(
            CreateReviewCommand::fromRequest($request, auth()->id())
        );

        return ReviewResource::make($review)
            ->additional(['message' => 'Sharhingiz moderatsiyaga yuborildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $id, UpdateReviewRequest $request): JsonResponse
    {
        $review = $this->updateHandler->handle(new UpdateReviewCommand(
            reviewId: $id,
            userId:   auth()->id(),
            dto:      UpdateReviewDTO::fromRequest($request),
        ));

        return ReviewResource::make($review)
            ->additional(['message' => 'Sharh yangilandi, qayta moderatsiyaga yuborildi'])
            ->response();
    }

    public function myReviews(): JsonResponse
    {
        $reviews = $this->getMyReviewsHandler->handle(
            new GetMyReviewsQuery(auth()->id())
        );

        return ReviewResource::collection($reviews)->response();
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    public function pendingReviews(): JsonResponse
    {
        $reviews = $this->getPendingReviewsHandler->handle(new GetPendingReviewsQuery());

        return ReviewResource::collection($reviews)->response();
    }

    public function approve(int $id): JsonResponse
    {
        $this->approveHandler->handle(new ApproveReviewCommand($id));

        return response()->json(['message' => 'Sharh tasdiqlandi']);
    }

    public function reject(int $id, RejectReviewRequest $request): JsonResponse
    {
        $this->rejectHandler->handle(new RejectReviewCommand(
            reviewId: $id,
            reason:   (string) $request->input('reason'),
        ));

        return response()->json(['message' => 'Sharh rad etildi']);
    }
}
