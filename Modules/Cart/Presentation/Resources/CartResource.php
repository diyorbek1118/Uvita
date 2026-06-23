<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deliveryPrice = (int) config('cart.delivery_price', 15000);

        if (!$this->resource) {
            return [
                'items'          => [],
                'total'          => 0,
                'delivery_price' => $deliveryPrice,
                'grand_total'    => $deliveryPrice,
                'items_count'    => 0,
            ];
        }

        $items = $this->items->map(fn(CartItemModel $item) => [
            'id'       => $item->id,
            'product'  => [
                'id'    => $item->product->id,
                'name'  => $item->product->name,
                'price' => $item->product->price,
                'stock' => $item->product->stock,
            ],
            'quantity' => $item->quantity,
            'subtotal' => $item->product->price * $item->quantity,
        ])->values()->all();

        $total = (int) array_sum(array_column($items, 'subtotal'));

        return [
            'items'          => $items,
            'total'          => $total,
            'delivery_price' => $deliveryPrice,
            'grand_total'    => $total + $deliveryPrice,
            'items_count'    => count($items),
        ];
    }
}
