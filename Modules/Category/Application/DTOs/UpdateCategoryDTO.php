<?php

declare(strict_types=1);

namespace Modules\Category\Application\DTOs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final readonly class UpdateCategoryDTO
{
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $image,
        public ?int    $parentId,
        public bool    $isActive,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $name = $request->string('name')->trim()->toString();

        return new self(
            name:     $name,
            slug:     $request->input('slug') ?? Str::slug($name),
            image:    $request->input('image'),
            parentId: $request->input('parent_id') ? (int) $request->input('parent_id') : null,
            isActive: (bool) $request->input('is_active', true),
        );
    }
}
