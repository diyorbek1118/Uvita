<?php

declare(strict_types=1);

namespace Modules\Category\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'slug'     => $this->slug,
            'image'    => $this->image,
            'parentId' => $this->parent_id,
            'isActive' => $this->is_active,
        ];
    }
}
