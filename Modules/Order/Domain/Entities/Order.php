<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\CannotCancelOrderException;
use Modules\Order\Domain\Exceptions\InvalidStatusTransitionException;
use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Domain\ValueObjects\Money;

class Order
{
    public private(set) OrderStatus $status;
    public private(set) ?int        $courierId;
    public private(set) int         $notFoundCount;
    public private(set) ?string     $courierNote;

    /** @param OrderItem[] $items */
    public function __construct(
        public readonly ?int            $id,
        public readonly int             $userId,
        OrderStatus                     $status,
        public readonly DeliveryAddress $address,
        public readonly string          $phone,
        public readonly ?string         $phoneSecondary,
        public readonly DeliveryTime    $deliveryTime,
        public readonly Money           $deliveryPrice,
        public readonly Money           $totalPrice,
        public readonly Money           $grandTotal,
        public readonly array           $items,
        ?string                         $courierNote = null,
        ?int                            $courierId = null,
        int                             $notFoundCount = 0,
    ) {
        $this->status        = $status;
        $this->courierId     = $courierId;
        $this->notFoundCount = $notFoundCount;
        $this->courierNote   = $courierNote;
    }

    public function markAsPaid(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new InvalidStatusTransitionException(
                "To'lov faqat 'pending' statusda amalga oshiriladi."
            );
        }
        $this->status = OrderStatus::PAID;
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::PAID) {
            throw new InvalidStatusTransitionException(
                "Tasdiqlash faqat 'paid' statusda amalga oshiriladi."
            );
        }
        $this->status = OrderStatus::CONFIRMED;
    }

    public function markReadyToDeliver(?string $courierNote = null): void
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            throw new InvalidStatusTransitionException(
                "Yig'ish faqat 'confirmed' statusda amalga oshiriladi."
            );
        }
        if ($courierNote !== null) {
            $this->courierNote = $courierNote;
        }
        $this->status = OrderStatus::READY_TO_DELIVER;
    }

    public function markDelivering(?int $courierId = null): void
    {
        if (!in_array($this->status, [OrderStatus::READY_TO_DELIVER, OrderStatus::DELIVERY_ISSUE], true)) {
            throw new InvalidStatusTransitionException(
                "Yetkazish faqat 'ready_to_deliver' yoki 'delivery_issue' statusda boshlanadi."
            );
        }
        if ($courierId !== null) {
            $this->courierId = $courierId;
        }
        $this->status = OrderStatus::DELIVERING;
    }

    public function markDelivered(): void
    {
        if ($this->status !== OrderStatus::DELIVERING) {
            throw new InvalidStatusTransitionException(
                "Yetkazildi faqat 'delivering' statusda belgilanadi."
            );
        }
        $this->status = OrderStatus::DELIVERED;
    }

    public function incrementNotFound(): void
    {
        $this->notFoundCount++;
        if ($this->notFoundCount >= 3) {
            $this->markDeliveryIssue();
        }
    }

    public function markDeliveryIssue(): void
    {
        if ($this->status !== OrderStatus::DELIVERING) {
            throw new InvalidStatusTransitionException(
                "Muammo faqat 'delivering' statusda belgilanadi."
            );
        }
        $this->status = OrderStatus::DELIVERY_ISSUE;
    }

    public function assignCourier(int $courierId): void
    {
        $this->courierId = $courierId;
    }

    public function resolveDeliveryIssue(string $action): void
    {
        if ($this->status !== OrderStatus::DELIVERY_ISSUE) {
            throw new InvalidStatusTransitionException(
                "Hal qilish faqat 'delivery_issue' statusida mumkin."
            );
        }
        $this->status = match ($action) {
            'reschedule' => OrderStatus::DELIVERING,
            'cancel'     => OrderStatus::CANCELLED,
            default      => throw new InvalidStatusTransitionException("Noto'g'ri amal: {$action}"),
        };
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new CannotCancelOrderException(
                "Buyurtma faqat 'pending' statusda bekor qilinadi."
            );
        }
        $this->status = OrderStatus::CANCELLED;
    }
}
