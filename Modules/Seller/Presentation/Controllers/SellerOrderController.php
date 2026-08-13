<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Seller\Application\Handlers\GetSellerOrdersHandler;
use Modules\Seller\Application\Queries\GetSellerOrdersQuery;

final class SellerOrderController extends Controller
{
    public function __construct(
        private readonly GetSellerOrdersHandler $handler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->handler->handle(new GetSellerOrdersQuery(
            sellerId: (int) auth('sanctum')->id(),
            perPage: (int) $request->integer('per_page', 30),
        ));

        return response()->json($orders);
    }
}
