<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\CannotCancelOrderException;
use Modules\Order\Domain\Exceptions\InvalidStatusTransitionException;
use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private function makeOrder(OrderStatus $status = OrderStatus::PENDING, int $notFoundCount = 0, ?int $courierId = null): Order
    {
        return new Order(
            id:             1,
            userId:         10,
            status:         $status,
            address:        new DeliveryAddress('Toshkent', 'Yunusobod', 'Navoiy', '1'),
            phone:          '+998901234567',
            phoneSecondary: null,
            deliveryTime:   new DeliveryTime('Ertaga 14:00-18:00'),
            deliveryPrice:  new Money(15000),
            totalPrice:     new Money(100000),
            grandTotal:     new Money(115000),
            items:          [],
            courierNote:    null,
            courierId:      $courierId,
            notFoundCount:  $notFoundCount,
        );
    }

    // ─── markAsPaid ───────────────────────────────────────────────────────────

    public function test_mark_as_paid_from_pending(): void
    {
        $order = $this->makeOrder(OrderStatus::PENDING);
        $order->markAsPaid();

        $this->assertSame(OrderStatus::PAID, $order->status);
    }

    public function test_mark_as_paid_from_non_pending_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::PAID)->markAsPaid();
    }

    // ─── confirm ──────────────────────────────────────────────────────────────

    public function test_confirm_from_paid(): void
    {
        $order = $this->makeOrder(OrderStatus::PAID);
        $order->confirm();

        $this->assertSame(OrderStatus::CONFIRMED, $order->status);
    }

    public function test_confirm_from_non_paid_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::PENDING)->confirm();
    }

    // ─── markReadyToDeliver ───────────────────────────────────────────────────

    public function test_ready_to_deliver_from_confirmed(): void
    {
        $order = $this->makeOrder(OrderStatus::CONFIRMED);
        $order->markReadyToDeliver();

        $this->assertSame(OrderStatus::READY_TO_DELIVER, $order->status);
    }

    public function test_ready_to_deliver_sets_courier_note(): void
    {
        $order = $this->makeOrder(OrderStatus::CONFIRMED);
        $order->markReadyToDeliver('Mo\'rtga ehtiyotkorlik');

        $this->assertSame("Mo'rtga ehtiyotkorlik", $order->courierNote);
    }

    public function test_ready_to_deliver_from_non_confirmed_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::PAID)->markReadyToDeliver();
    }

    // ─── markDelivering ───────────────────────────────────────────────────────

    public function test_delivering_from_ready_to_deliver(): void
    {
        $order = $this->makeOrder(OrderStatus::READY_TO_DELIVER);
        $order->markDelivering(5);

        $this->assertSame(OrderStatus::DELIVERING, $order->status);
        $this->assertSame(5, $order->courierId);
    }

    public function test_delivering_from_delivery_issue(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERY_ISSUE);
        $order->markDelivering();

        $this->assertSame(OrderStatus::DELIVERING, $order->status);
    }

    public function test_delivering_from_invalid_status_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::PENDING)->markDelivering();
    }

    // ─── markDelivered ────────────────────────────────────────────────────────

    public function test_mark_delivered_from_delivering(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERING);
        $order->markDelivered();

        $this->assertSame(OrderStatus::DELIVERED, $order->status);
    }

    public function test_mark_delivered_from_non_delivering_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::READY_TO_DELIVER)->markDelivered();
    }

    // ─── incrementNotFound ────────────────────────────────────────────────────

    public function test_increment_not_found_first_attempt_stays_delivering(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERING, notFoundCount: 0);
        $order->incrementNotFound(3);

        $this->assertSame(OrderStatus::DELIVERING, $order->status);
        $this->assertSame(1, $order->notFoundCount);
    }

    public function test_increment_not_found_second_attempt_stays_delivering(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERING, notFoundCount: 1);
        $order->incrementNotFound(3);

        $this->assertSame(OrderStatus::DELIVERING, $order->status);
        $this->assertSame(2, $order->notFoundCount);
    }

    public function test_increment_not_found_third_attempt_becomes_delivery_issue(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERING, notFoundCount: 2);
        $order->incrementNotFound(3);

        $this->assertSame(OrderStatus::DELIVERY_ISSUE, $order->status);
        $this->assertSame(3, $order->notFoundCount);
    }

    // ─── resolveDeliveryIssue ─────────────────────────────────────────────────

    public function test_resolve_reschedule_changes_to_delivering(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERY_ISSUE);
        $order->resolveDeliveryIssue('reschedule');

        $this->assertSame(OrderStatus::DELIVERING, $order->status);
    }

    public function test_resolve_cancel_changes_to_cancelled(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERY_ISSUE);
        $order->resolveDeliveryIssue('cancel');

        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    public function test_resolve_invalid_action_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::DELIVERY_ISSUE)->resolveDeliveryIssue('refund');
    }

    public function test_resolve_from_non_delivery_issue_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->makeOrder(OrderStatus::DELIVERING)->resolveDeliveryIssue('reschedule');
    }

    // ─── cancel ───────────────────────────────────────────────────────────────

    public function test_cancel_from_pending(): void
    {
        $order = $this->makeOrder(OrderStatus::PENDING);
        $order->cancel();

        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    public function test_cancel_from_paid_throws(): void
    {
        $this->expectException(CannotCancelOrderException::class);

        $this->makeOrder(OrderStatus::PAID)->cancel();
    }

    public function test_cancel_from_confirmed_throws(): void
    {
        $this->expectException(CannotCancelOrderException::class);

        $this->makeOrder(OrderStatus::CONFIRMED)->cancel();
    }

    // ─── assignCourier ────────────────────────────────────────────────────────

    public function test_assign_courier_sets_courier_id(): void
    {
        $order = $this->makeOrder(OrderStatus::READY_TO_DELIVER);
        $order->assignCourier(7);

        $this->assertSame(7, $order->courierId);
    }
}
