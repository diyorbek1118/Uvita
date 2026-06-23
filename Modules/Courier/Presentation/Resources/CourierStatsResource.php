<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Courier\Domain\ValueObjects\CourierStats;

class CourierStatsResource extends JsonResource
{
    public function __construct(private readonly CourierStats $stats) {}

    public function toArray(Request $request): array
    {
        return [
            'total_delivered' => $this->stats->totalDelivered,
            'total_not_found' => $this->stats->totalNotFound,
            'total_active'    => $this->stats->totalActive,
            'success_rate'    => $this->stats->successRate,
        ];
    }
}
