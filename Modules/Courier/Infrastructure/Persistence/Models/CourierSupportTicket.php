<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Courier\Domain\Enums\SupportTicketStatus;

final class CourierSupportTicket extends Model
{
    protected $fillable = [
        'courier_id', 'order_id', 'category', 'message', 'status',
        'admin_reply', 'resolved_by', 'resolved_at',
    ];

    protected $casts = ['status' => SupportTicketStatus::class, 'resolved_at' => 'datetime'];
}
