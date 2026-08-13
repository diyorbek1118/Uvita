<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class DeliveryAssignment extends Model
{
    protected $fillable = [
        'order_id', 'courier_id', 'assigned_by', 'status', 'rejection_reason',
        'assigned_at', 'responded_at',
    ];

    protected $casts = [
        'status' => DeliveryAssignmentStatus::class,
        'assigned_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'courier_id');
    }
}
