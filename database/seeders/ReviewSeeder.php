<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

/**
 * Sharhlar — faqat yetkazilgan buyurtmalar uchun (bir buyurtma = bir sharh).
 * So'ngida mahsulot rating/reviews_count agregatlari yangilanadi.
 */
class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $deliveredOrders = OrderModel::where('status', 'delivered')->get();

        foreach ($deliveredOrders as $order) {
            if (! fake()->boolean(70)) {
                continue;
            }

            $productId = OrderItemModel::where('order_id', $order->id)->value('product_id');
            if ($productId === null) {
                continue;
            }

            ReviewModel::create([
                'order_id'   => $order->id,
                'user_id'    => $order->user_id,
                'product_id' => $productId,
                'rating'     => fake()->numberBetween(3, 5),
                'comment'    => fake()->optional()->paragraph(),
                'status'     => 'approved',
                'is_visible' => true,
                'admin_note' => null,
            ]);
        }

        $this->refreshProductRatings();
    }

    private function refreshProductRatings(): void
    {
        $aggregates = ReviewModel::query()
            ->where('status', 'approved')
            ->where('is_visible', true)
            ->selectRaw('product_id, AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->groupBy('product_id')
            ->get();

        foreach ($aggregates as $row) {
            Product::where('id', $row->product_id)->update([
                'rating'        => round((float) $row->avg_rating, 1),
                'reviews_count' => (int) $row->cnt,
            ]);
        }
    }
}
