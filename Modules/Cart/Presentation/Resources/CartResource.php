<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Resources;

use App\Shared\Services\Fee\OrderFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mijoz mahsulot summasi + 15% xizmat haqi to'laydi. Yetkazish TEKIN,
        // kuryer haqi platformadan (mijozga ko'rinmaydi).
        if (!$this->resource) {
            return [
                'items'       => [],
                'total'       => 0,
                'service_fee' => 0,
                'grand_total' => 0,
                'items_count' => 0,
            ];
        }

        $items = $this->items->map(fn(CartItemModel $item) => [
            'id'          => $item->id,
            'product_id'  => $item->product_id,
            'name'        => $item->product->name,
            'price'       => $item->product->price,
            'quantity'    => $item->quantity,
            'images'      => $item->product->images ?? [],
            'category'    => $item->product->category ? [
                'id'   => $item->product->category->id,
                'name' => $item->product->category->name,
            ] : null,
            'product'     => [
                'id'    => $item->product->id,
                'name'  => $item->product->name,
                'price' => $item->product->price,
                'stock' => $item->product->stock,
                'images' => $item->product->images ?? [],
                'category' => $item->product->category ? [
                    'id'   => $item->product->category->id,
                    'name' => $item->product->category->name,
                ] : null,
            ],
            'subtotal'    => $item->product->price * $item->quantity,
        ])->values()->all();

        $total      = (int) array_sum(array_column($items, 'subtotal'));
        $financials = (new OrderFeeCalculator())->calculate($total);

        return [
            'items'       => $items,
            'total'       => $total,                            // mahsulotlar summasi
            'service_fee' => $financials->platformFeeGross,     // 15% xizmat haqi
            'grand_total' => $financials->customerTotal,        // jami to'lov
            'items_count' => count($items),
        ];
    }
}
