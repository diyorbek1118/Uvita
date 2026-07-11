<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetDashboardProductByIdHandler;
use Modules\Admin\Application\Handlers\GetDashboardProductsHandler;
use Modules\Admin\Application\Handlers\UpdateDashboardProductHandler;
use Modules\Admin\Application\Queries\GetDashboardProductByIdQuery;
use Modules\Admin\Application\Queries\GetDashboardProductsQuery;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Admin\Presentation\Resources\DashboardProductResource;
use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\CreateProductHandler;
use Modules\Product\Application\Handlers\DeleteProductHandler;
use Modules\Product\Presentation\Requests\CreateProductRequest;
use Modules\Product\Presentation\Requests\UpdateProductRequest;

final class DashboardProductController extends Controller
{
    public function __construct(
        private readonly GetDashboardProductsHandler    $listHandler,
        private readonly GetDashboardProductByIdHandler  $byIdHandler,
        private readonly CreateProductHandler            $createHandler,
        private readonly UpdateDashboardProductHandler   $updateHandler,
        private readonly DeleteProductHandler            $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->listHandler->handle(new GetDashboardProductsQuery(
            managerId:  $this->managerScopeId(),
            status:     $request->query('status'),
            categoryId: $request->filled('category_id') ? (int) $request->query('category_id') : null,
            search:     $request->query('search'),
            perPage:    (int) $request->query('per_page', 20),
        ));

        return DashboardProductResource::collection($products)->response();
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = $this->listHandler->handle(new GetDashboardProductsQuery(
            managerId: $this->managerScopeId(),
            maxStock:  (int) $request->query('threshold', 10),
            perPage:   (int) $request->query('per_page', 20),
        ));

        return DashboardProductResource::collection($products)->response();
    }

    public function show(int $product): JsonResponse
    {
        $result = $this->byIdHandler->handle(new GetDashboardProductByIdQuery(
            id:        $product,
            managerId: $this->managerScopeId(),
        ));

        return DashboardProductResource::make($result)->response();
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        /** @var Staff $user */
        $user      = auth('sanctum')->user();
        $managerId = $user->role === StaffRole::MANAGER ? $user->id : null;

        $result = $this->createHandler->handle(
            CreateProductCommand::fromRequest($request, $managerId)
        );

        $message = $managerId !== null
            ? 'Mahsulot yaratildi, moderatsiya kutilmoqda'
            : 'Mahsulot yaratildi';

        return DashboardProductResource::make($result->load(['manager', 'category']))
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        /** @var Staff $user */
        $user = auth('sanctum')->user();

        $result = $this->updateHandler->handle(
            UpdateProductCommand::fromRequest($request, $product),
            $user
        );

        return DashboardProductResource::make($result->load(['manager', 'category']))
            ->additional(['message' => 'Mahsulot yangilandi'])
            ->response();
    }

    public function destroy(int $product): Response
    {
        $this->deleteHandler->handle(new DeleteProductCommand($product));

        return response()->noContent();
    }

    private function managerScopeId(): ?int
    {
        /** @var Staff $user */
        $user = auth('sanctum')->user();

        return $user->role === StaffRole::MANAGER ? $user->id : null;
    }
}
