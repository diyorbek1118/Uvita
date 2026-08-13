<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class MessageModel extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ConversationModel::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'sender_id');
    }
}
