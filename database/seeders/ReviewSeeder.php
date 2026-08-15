<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $comments = [
            ['rating' => 5, 'comment' => 'Mahsulotlar rasmdagidek, kartoshka quruq va yaxshi saralangan ekan. Yetkazish ham vaqtida bo‘ldi.'],
            ['rating' => 4, 'comment' => 'Bug‘doy toza, qoplari butun yetib keldi. Keyingi safar ham shu sellerdan olamiz.'],
        ];

        $orders = OrderModel::where('status', 'delivered')->with('items')->get()->values();
        foreach ($orders as $index => $order) {
            $item = $order->items->first();
            if (! $item) {
                continue;
            }
            $review = $comments[$index % count($comments)];
            ReviewModel::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'product_id' => $item->product_id,
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'status' => 'approved',
                'is_visible' => true,
                'admin_note' => null,
            ]);
        }

        Product::query()->update(['rating' => 0, 'reviews_count' => 0]);
        foreach (ReviewModel::selectRaw('product_id, AVG(rating) avg_rating, COUNT(*) cnt')->groupBy('product_id')->get() as $row) {
            Product::whereKey($row->product_id)->update(['rating' => round((float) $row->avg_rating, 1), 'reviews_count' => (int) $row->cnt]);
        }
    }
}
