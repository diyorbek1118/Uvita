<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetAllTransactionsHandler;
use Modules\Admin\Application\Handlers\GetTransactionStatsHandler;
use Modules\Admin\Application\Queries\GetAllTransactionsQuery;
use Modules\Admin\Presentation\Resources\TransactionResource;

final class AdminTransactionController extends Controller
{
    public function __construct(
        private readonly GetAllTransactionsHandler  $allHandler,
        private readonly GetTransactionStatsHandler $statsHandler,
    ) {}

    public function index(): JsonResponse
    {
        $transactions = $this->allHandler->handle(new GetAllTransactionsQuery(
            provider: request('provider'),
            status:   request('status'),
            dateFrom: request('date_from'),
            dateTo:   request('date_to'),
        ));

        return TransactionResource::collection($transactions)->response();
    }

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle();

        return response()->json(['data' => $stats]);
    }
}
