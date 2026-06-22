<?php

declare(strict_types=1);

namespace Modules\Product\Application\Queries;

use Illuminate\Http\Request;

final readonly class GetProductListQuery
{
    public function __construct(
        public int $perPage = 15,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            perPage: (int) $request->input('per_page', 15),
        );
    }
}
