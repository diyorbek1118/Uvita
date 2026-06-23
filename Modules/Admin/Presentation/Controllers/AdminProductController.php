<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Commands\ApproveProductCommand;
use Modules\Admin\Application\Commands\RejectProductCommand;
use Modules\Admin\Application\Handlers\ApproveProductHandler;
use Modules\Admin\Application\Handlers\GetAllProductsHandler;
use Modules\Admin\Application\Handlers\GetPendingProductsHandler;
use Modules\Admin\Application\Handlers\RejectProductHandler;
use Modules\Admin\Application\Queries\GetAllProductsQuery;
use Modules\Admin\Application\Queries\GetPendingProductsQuery;
use Modules\Admin\Presentation\Requests\RejectProductRequest;
use Modules\Admin\Presentation\Resources\AdminProductResource;

final class AdminProductController extends Controller
{
    public function __construct(
        private readonly GetPendingProductsHandler $pendingHandler,
        private readonly GetAllProductsHandler     $allHandler,
        private readonly ApproveProductHandler     $approveHandler,
        private readonly RejectProductHandler      $rejectHandler,
    ) {}

    public function pendingProducts(): JsonResponse
    {
        $products = $this->pendingHandler->handle(new GetPendingProductsQuery());

        return AdminProductResource::collection($products)->response();
    }

    public function allProducts(): JsonResponse
    {
        $products = $this->allHandler->handle(
            new GetAllProductsQuery(status: request('status'))
        );

        return AdminProductResource::collection($products)->response();
    }

    public function approve(int $id): JsonResponse
    {
        $product = $this->approveHandler->handle(new ApproveProductCommand($id));

        return AdminProductResource::make($product)
            ->additional(['message' => 'Mahsulot tasdiqlandi'])
            ->response();
    }

    public function reject(int $id, RejectProductRequest $request): JsonResponse
    {
        $product = $this->rejectHandler->handle(
            new RejectProductCommand(id: $id, reason: $request->input('reason'))
        );

        return AdminProductResource::make($product)
            ->additional(['message' => 'Mahsulot rad etildi'])
            ->response();
    }
}
