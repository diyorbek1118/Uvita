<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Commands\ApproveProductCommand;
use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Application\Commands\RejectProductCommand;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\ApproveProductHandler;
use Modules\Product\Application\Handlers\CreateProductHandler;
use Modules\Product\Application\Handlers\DeleteProductHandler;
use Modules\Product\Application\Handlers\GetProductByIdHandler;
use Modules\Product\Application\Handlers\GetProductListHandler;
use Modules\Product\Application\Handlers\RejectProductHandler;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Application\Queries\GetProductByIdQuery;
use Modules\Product\Application\Queries\GetProductListQuery;
use Modules\Product\Presentation\Requests\CreateProductRequest;
use Modules\Product\Presentation\Requests\RejectProductRequest;
use Modules\Product\Presentation\Requests\UpdateProductRequest;
use Modules\Product\Presentation\Resources\ProductResource;

final class ProductController extends Controller
{
    public function __construct(
        private readonly GetProductListHandler  $listHandler,
        private readonly GetProductByIdHandler  $byIdHandler,
        private readonly CreateProductHandler   $createHandler,
        private readonly UpdateProductHandler   $updateHandler,
        private readonly DeleteProductHandler   $deleteHandler,
        private readonly ApproveProductHandler  $approveHandler,
        private readonly RejectProductHandler   $rejectHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->listHandler->handle(
            GetProductListQuery::fromRequest($request)
        );

        return ProductResource::collection($products)->response();
    }

    public function show(int $product): JsonResponse
    {
        $result = $this->byIdHandler->handle(new GetProductByIdQuery($product));

        return ProductResource::make($result)->response();
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $managerId = auth('sanctum')->id();

        $result = $this->createHandler->handle(
            CreateProductCommand::fromRequest($request, $managerId)
        );

        $message = $managerId !== null
            ? 'Mahsulot yaratildi, moderatsiya kutilmoqda'
            : 'Mahsulot yaratildi';

        return ProductResource::make($result)
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $result = $this->updateHandler->handle(
            UpdateProductCommand::fromRequest($request, $product)
        );

        return ProductResource::make($result)
            ->additional(['message' => 'Mahsulot yangilandi'])
            ->response();
    }

    public function destroy(int $product): \Illuminate\Http\Response
    {
        $this->deleteHandler->handle(new DeleteProductCommand($product));

        return response()->noContent();
    }

    public function approve(int $product): JsonResponse
    {
        $result = $this->approveHandler->handle(new ApproveProductCommand($product));

        return ProductResource::make($result)
            ->additional(['message' => 'Mahsulot tasdiqlandi'])
            ->response();
    }

    public function reject(RejectProductRequest $request, int $product): JsonResponse
    {
        $result = $this->rejectHandler->handle(
            new RejectProductCommand($product, $request->validated('reason'))
        );

        return ProductResource::make($result)
            ->additional(['message' => 'Mahsulot rad etildi'])
            ->response();
    }
}
