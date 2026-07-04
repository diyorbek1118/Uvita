<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class CartItemModel extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartModel::class, 'cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
