<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\Infrastructure\External\Click\ClickWebhookHandler;

class ClickWebhookController extends Controller
{
    public function __construct(
        private readonly ClickWebhookHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->handler->handle($request);
        } catch (\Throwable $e) {
            return response()->json([
                'error'      => -9,
                'error_note' => $e->getMessage(),
            ], 200);
        }

        return response()->json($result);
    }
}
