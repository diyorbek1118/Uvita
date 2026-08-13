<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Demo bitimlar — xaridorlar sotuvchilarga buyurtma yuborgan holatlar.
 */
class DealSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::pluck('id')->all();
        if (empty($buyers)) {
            return;
        }

        // Faol e'lonlardan tasodifiy tanlaymiz
        $active = ListingModel::where('status', 'active')->with('seller')->get();
        if ($active->isEmpty()) {
            return;
        }

        $created = 0;

        foreach ($active->take(6) as $listing) {
            // Sotuvchidan boshqa xaridor tanlaymiz
            $buyer = null;
            foreach ($buyers as $candidate) {
                if ($candidate !== $listing->seller_id) {
                    $buyer = $candidate;
                    break;
                }
            }
            if ($buyer === null) {
                continue;
            }

            $quantity = max(1, (int) round($listing->quantity * 0.1));
            $statuses = ['pending', 'confirmed', 'completed'];
            $status   = $statuses[$created % count($statuses)];

            DealModel::create([
                'listing_id'  => $listing->id,
                'seller_id'   => $listing->seller_id,
                'buyer_id'    => $buyer,
                'quantity'    => $quantity,
                'unit'        => $listing->unit,
                'total_price' => $listing->price * $quantity,
                'status'      => $status,
            ]);

            if ($status === 'completed') {
                $listing->update(['status' => 'sold']);
            }

            $created++;
        }

        $this->command->info("✅ Bitimlar: {$created} ta.");
    }
}
