<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\Domain\Exceptions\InvalidSignatureException;
use Modules\Payment\Infrastructure\External\Payme\PaymeWebhookHandler;

class PaymeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymeWebhookHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->handler->handle($request);
        } catch (InvalidSignatureException $e) {
            return response()->json([
                'error' => ['code' => -32504, 'message' => $e->getMessage(), 'data' => null],
                'id'    => $request->input('id'),
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => ['code' => -32400, 'message' => $e->getMessage(), 'data' => null],
                'id'    => $request->input('id'),
            ], 200);
        }

        return response()->json($result);
    }
}
