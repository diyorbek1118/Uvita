<?php

declare(strict_types=1);

namespace Modules\Category\Application\Queries;

use Illuminate\Http\Request;

final readonly class GetCategoryListQuery
{
    public function __construct(
        public int  $perPage  = 15,
        public ?int $parentId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            perPage:  (int) $request->input('per_page', 15),
            parentId: $request->input('parent_id') ? (int) $request->input('parent_id') : null,
        );
    }
}
