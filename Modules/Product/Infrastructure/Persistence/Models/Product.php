<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;
use Modules\Product\Domain\Enums\ProductStatusEnum;

final class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'status',
        'images',
        'category_id',
        'manager_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'integer',
            'stock'      => 'integer',
            'images'     => 'array',
            'status'     => ProductStatusEnum::class,
            'manager_id' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}
