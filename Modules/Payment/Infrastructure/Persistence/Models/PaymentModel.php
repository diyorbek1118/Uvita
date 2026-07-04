<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;

class PaymentModel extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'provider',
        'transaction_id',
        'provider_transaction_id',
        'amount',
        'status',
        'payload',
    ];

    protected $casts = [
        'status'   => PaymentStatus::class,
        'provider' => PaymentProvider::class,
        'payload'  => 'array',
        'amount'   => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
}
