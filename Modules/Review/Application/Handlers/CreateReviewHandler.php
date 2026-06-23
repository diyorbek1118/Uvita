<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Review\Application\Commands\CreateReviewCommand;
use Modules\Review\Domain\Entities\Review;
use Modules\Review\Domain\Exceptions\OrderNotDeliveredException;
use Modules\Review\Domain\Exceptions\ReviewAlreadyExistsException;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class CreateReviewHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    public function handle(CreateReviewCommand $command): ReviewModel
    {
        $dto   = $command->dto;
        $order = OrderModel::findOrFail($dto->orderId);

        if ($order->user_id !== $dto->userId) {
            abort(403, "Bu buyurtma sizga tegishli emas.");
        }

        if ($order->status !== OrderStatus::DELIVERED) {
            throw new OrderNotDeliveredException("Buyurtma hali yetkazilmagan.");
        }

        if ($this->reviews->findByOrderId($dto->orderId) !== null) {
            throw new ReviewAlreadyExistsException("Bu buyurtma uchun sharh allaqachon yozilgan.");
        }

        $review = new Review(
            id:        null,
            orderId:   $dto->orderId,
            userId:    $dto->userId,
            productId: $dto->productId,
            rating:    $dto->rating,
            comment:   $dto->comment,
        );

        $reviewId = $this->reviews->save($review);

        return ReviewModel::findOrFail($reviewId);
    }
}
