<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment;

use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentEntityTest extends TestCase
{
    private function makePayment(PaymentStatus $status = PaymentStatus::PENDING): Payment
    {
        return new Payment(
            id:            1,
            orderId:       10,
            provider:      PaymentProvider::PAYME,
            transactionId: null,
            amount:        150000,
            status:        $status,
        );
    }

    public function test_initial_status_is_pending(): void
    {
        $payment = $this->makePayment();

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertNull($payment->transactionId);
    }

    // ─── markAsPaid ───────────────────────────────────────────────────────────

    public function test_mark_as_paid_sets_paid_status(): void
    {
        $payment = $this->makePayment();
        $payment->markAsPaid('txn_abc123');

        $this->assertSame(PaymentStatus::PAID, $payment->status);
    }

    public function test_mark_as_paid_stores_transaction_id(): void
    {
        $payment = $this->makePayment();
        $payment->markAsPaid('txn_abc123');

        $this->assertSame('txn_abc123', $payment->transactionId);
    }

    // ─── markAsFailed ─────────────────────────────────────────────────────────

    public function test_mark_as_failed_sets_failed_status(): void
    {
        $payment = $this->makePayment();
        $payment->markAsFailed();

        $this->assertSame(PaymentStatus::FAILED, $payment->status);
    }

    public function test_mark_as_failed_does_not_set_transaction_id(): void
    {
        $payment = $this->makePayment();
        $payment->markAsFailed();

        $this->assertNull($payment->transactionId);
    }

    // ─── markAsCancelled ──────────────────────────────────────────────────────

    public function test_mark_as_cancelled_sets_cancelled_status(): void
    {
        $payment = $this->makePayment();
        $payment->markAsCancelled();

        $this->assertSame(PaymentStatus::CANCELLED, $payment->status);
    }

    // ─── provider / amount ────────────────────────────────────────────────────

    public function test_provider_and_amount_are_stored(): void
    {
        $payment = new Payment(
            id:            null,
            orderId:       5,
            provider:      PaymentProvider::CLICK,
            transactionId: null,
            amount:        200000,
        );

        $this->assertSame(PaymentProvider::CLICK, $payment->provider);
        $this->assertSame(200000, $payment->amount);
    }
}
