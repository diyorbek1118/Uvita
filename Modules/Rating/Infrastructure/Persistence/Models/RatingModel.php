<?php

declare(strict_types=1);

namespace Modules\Rating\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class RatingModel extends Model
{
    use HasFactory;

    protected $table = 'ratings';

    protected $fillable = [
        'deal_id',
        'listing_id',
        'rater_id',
        'rated_id',
        'stars',
        'comment',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(DealModel::class, 'deal_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ListingModel::class, 'listing_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'rater_id');
    }

    public function rated(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'rated_id');
    }
}
