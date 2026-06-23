<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;

final class ClearCartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $userId) {}

    public function handle(CartRepositoryInterface $cartRepository): void
    {
        $cart = $cartRepository->findByUserId($this->userId);

        if ($cart !== null && $cart->id !== null) {
            $cartRepository->clear($cart->id);
        }
    }
}
