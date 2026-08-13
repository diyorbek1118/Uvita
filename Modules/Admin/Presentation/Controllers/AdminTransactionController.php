<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetAllTransactionsHandler;
use Modules\Admin\Application\Handlers\GetTransactionStatsHandler;
use Modules\Admin\Application\Queries\GetAllTransactionsQuery;
use Modules\Admin\Presentation\Resources\TransactionResource;

final class AdminTransactionController extends Controller
{
    public function __construct(
        private readonly GetAllTransactionsHandler $allHandler,
        private readonly GetTransactionStatsHandler $statsHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => ['nullable', 'in:all,payme,click,uzum'],
            'status' => ['nullable', 'in:all,pending,paid,failed,cancelled,refund_pending,refunded'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $transactions = $this->allHandler->handle(new GetAllTransactionsQuery(
            provider: $request->query('provider'),
            status: $request->query('status'),
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
        ));

        return TransactionResource::collection($transactions)->response();
    }

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle();

        return response()->json(['data' => $stats]);
    }
}
