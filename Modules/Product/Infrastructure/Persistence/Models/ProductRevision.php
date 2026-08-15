<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Domain\Enums\ProductRevisionStatusEnum;

final class ProductRevision extends Model
{
    protected $fillable = [
        'product_id', 'seller_id', 'seller_profile_id', 'version', 'payload', 'fee_snapshot', 'status',
        'rejection_reason', 'reviewed_by_id', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'fee_snapshot' => 'array',
            'status' => ProductRevisionStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'seller_id');
    }
}
