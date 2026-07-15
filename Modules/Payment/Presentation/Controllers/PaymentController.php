<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Payment\Application\Commands\CreatePaymentCommand;
use Modules\Payment\Application\Handlers\CreatePaymentHandler;
use Modules\Payment\Presentation\Requests\CreatePaymentRequest;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CreatePaymentHandler $createHandler,
    ) {}

    public function create(CreatePaymentRequest $request): JsonResponse
    {
        $result = $this->createHandler->handle(new CreatePaymentCommand(
            orderId:  (int) $request->validated('order_id'),
            provider: (string) $request->validated('provider'),
        ));

        return response()->json([
            'data'    => $result,
            'message' => "To'lov sahifasi tayyor",
        ], 200);
    }
}
