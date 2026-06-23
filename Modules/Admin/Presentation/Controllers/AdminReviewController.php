<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetReviewStatsHandler;

final class AdminReviewController extends Controller
{
    public function __construct(
        private readonly GetReviewStatsHandler $statsHandler,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle();

        return response()->json(['data' => $stats]);
    }
}
