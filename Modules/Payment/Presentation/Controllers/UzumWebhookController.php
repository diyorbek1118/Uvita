<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\Domain\Exceptions\InvalidSignatureException;
use Modules\Payment\Infrastructure\External\Uzum\UzumWebhookHandler;

class UzumWebhookController extends Controller
{
    public function __construct(
        private readonly UzumWebhookHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->handler->handle($request);
        } catch (InvalidSignatureException $e) {
            return response()->json(['status' => -1, 'errorMessage' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            return response()->json(['status' => -1, 'errorMessage' => $e->getMessage()], 200);
        }

        return response()->json($result);
    }
}
