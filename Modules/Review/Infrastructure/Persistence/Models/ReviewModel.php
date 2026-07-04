<?php

declare(strict_types=1);

namespace Modules\Review\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;
use Modules\Review\Domain\Enums\ReviewStatus;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class ReviewModel extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'order_id',
        'user_id',
        'product_id',
        'rating',
        'comment',
        'status',
        'is_visible',
        'admin_note',
    ];

    protected $casts = [
        'status'     => ReviewStatus::class,
        'is_visible' => 'boolean',
        'rating'     => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
