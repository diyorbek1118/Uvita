<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

/**
 * Buyurtma to'liq detali (dashboard) — status timeline + narx breakdown.
 * Moliyaviy breakdown faqat admin/super uchun ko'rinadi.
 */
class DashboardOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status->value,
            'customer'        => [
                'name'            => $this->whenLoaded('user', fn () => $this->user?->name),
                'phone'           => $this->phone,
                'phone_secondary' => $this->phone_secondary,
            ],
            'courier'         => $this->whenLoaded('courier', fn () => $this->courier ? [
                'id'   => $this->courier->id,
                'name' => $this->courier->name,
            ] : null),
            'address'         => $this->address,
            'delivery_time'   => $this->delivery_time,
            'courier_note'    => $this->courier_note,
            'not_found_count' => $this->not_found_count,
            'items'           => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity'     => $item->quantity,
                    'price'        => $item->price,
                    'subtotal'     => $item->price * $item->quantity,
                ])
            ),
            'pricing'         => [
                'total_price' => $this->total_price,   // mahsulotlar summasi (sotuvchiga)
                'service_fee' => $this->service_fee,   // 15% xizmat haqi (mijoz to'laydi)
                'grand_total' => $this->grand_total,   // mijoz to'lagan jami
            ],
            // Narx breakdown (platform_fee, courier_fee, ...) — faqat admin/super.
            'financials'      => $this->when($this->canSeeFinancials(), fn () => $this->financials),
            'timeline'        => [
                'created_at'        => $this->created_at?->toISOString(),
                'paid_at'           => $this->paid_at?->toISOString(),
                'confirmed_at'      => $this->confirmed_at?->toISOString(),
                'ready_at'          => $this->ready_at?->toISOString(),
                'delivering_at'     => $this->delivering_at?->toISOString(),
                'delivered_at'      => $this->delivered_at?->toISOString(),
                'delivery_issue_at' => $this->delivery_issue_at?->toISOString(),
                'cancelled_at'      => $this->cancelled_at?->toISOString(),
            ],
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }

    private function canSeeFinancials(): bool
    {
        $user = auth('sanctum')->user();

        return $user instanceof Staff
            && in_array($user->role, [StaffRole::ADMIN, StaffRole::SUPER_ADMIN], true);
    }
}
