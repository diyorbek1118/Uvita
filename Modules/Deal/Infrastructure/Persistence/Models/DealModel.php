<?php

declare(strict_types=1);

namespace Modules\Deal\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Deal\Domain\Enums\DealStatus;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class DealModel extends Model
{
    use HasFactory;

    protected $table = 'deals';

    protected $fillable = [
        'listing_id',
        'seller_id',
        'buyer_id',
        'quantity',
        'unit',
        'total_price',
        'status',
    ];

    protected $casts = [
        'status' => DealStatus::class,
        'quantity' => 'float',
        'total_price' => 'integer',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ListingModel::class, 'listing_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'buyer_id');
    }
}
