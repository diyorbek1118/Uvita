<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key'         => $this->resource['key'] ?? null,
            'value'       => $this->resource['value'] ?? null,
            'description' => $this->resource['description'] ?? null,
        ];
    }
}
