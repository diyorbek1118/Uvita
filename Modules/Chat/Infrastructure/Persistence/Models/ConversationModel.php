<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class ConversationModel extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'listing_id',
        'buyer_id',
        'seller_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ListingModel::class, 'listing_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageModel::class, 'conversation_id')->orderBy('id');
    }
}
