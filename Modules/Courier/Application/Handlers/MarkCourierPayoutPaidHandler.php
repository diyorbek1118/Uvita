<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Domain\Enums\CourierPayoutStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierPayout;

final class MarkCourierPayoutPaidHandler
{
    public function __construct(private readonly CourierNotifierInterface $notifier) {}

    public function handle(int $payoutId): CourierPayout
    {
        $payout = CourierPayout::findOrFail($payoutId);
        if ($payout->status !== CourierPayoutStatus::PENDING) {
            throw new DomainException('Faqat kutilayotgan hisob-kitob to‘landi deb belgilanadi.');
        }

        $payout->update(['status' => CourierPayoutStatus::PAID, 'paid_at' => now()]);
        if ($payout->courier_id !== null) {
            $this->notifier->notify(
                $payout->courier_id,
                'payout_paid',
                'Hisob-kitob to‘landi',
                number_format($payout->amount, 0, '.', ' ').' so‘m to‘landi.',
                ['payout_id' => $payout->id]
            );
        }

        return $payout->fresh();
    }
}
