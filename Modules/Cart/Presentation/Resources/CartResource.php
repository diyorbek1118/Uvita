<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Resources;

use App\Shared\Services\Fee\OrderFeeCalculator;
use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mijoz faqat seller kiritgan mahsulot narxini to'laydi.
        // Barcha komissiyalar ichki hisob bo'lib, mijozga ko'rinmaydi.
        if (! $this->resource) {
            return [
                'items' => [],
                'total' => 0,
                'service_fee' => 0,
                'grand_total' => 0,
                'items_count' => 0,
            ];
        }

        $items = $this->items->map(fn (CartItemModel $item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'price' => $item->product->price,
            'quantity' => $item->quantity,
            'minimum_order_quantity' => $item->product->minimum_order_quantity ?? 1,
            'images' => ImageUrlNormalizer::normalizeArray($item->product->images ?? []),
            'category' => $item->product->category ? [
                'id' => $item->product->category->id,
                'name' => $item->product->category->name,
            ] : null,
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'price' => $item->product->price,
                'stock' => $item->product->available_stock,
                'unit' => $item->product->unit ?? 'dona',
                'minimum_order_quantity' => $item->product->minimum_order_quantity ?? 1,
                'images' => ImageUrlNormalizer::normalizeArray($item->product->images ?? []),
                'category' => $item->product->category ? [
                    'id' => $item->product->category->id,
                    'name' => $item->product->category->name,
                ] : null,
            ],
            'subtotal' => $item->product->price * $item->quantity,
        ])->values()->all();

        $total = (int) array_sum(array_column($items, 'subtotal'));
        $financials = (new OrderFeeCalculator)->calculate($total);

        return [
            'items' => $items,
            'total' => $total,                            // mahsulotlar summasi
            'service_fee' => 0,
            'grand_total' => $financials->customerTotal,
            'items_count' => count($items),
        ];
    }
}
