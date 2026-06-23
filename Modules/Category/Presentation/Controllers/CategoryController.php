<?php

declare(strict_types=1);

namespace Modules\Category\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Category\Application\Commands\CreateCategoryCommand;
use Modules\Category\Application\Commands\DeleteCategoryCommand;
use Modules\Category\Application\Commands\UpdateCategoryCommand;
use Modules\Category\Application\Handlers\CreateCategoryHandler;
use Modules\Category\Application\Handlers\DeleteCategoryHandler;
use Modules\Category\Application\Handlers\GetCategoryByIdHandler;
use Modules\Category\Application\Handlers\GetCategoryListHandler;
use Modules\Category\Application\Handlers\UpdateCategoryHandler;
use Modules\Category\Application\Queries\GetCategoryByIdQuery;
use Modules\Category\Application\Queries\GetCategoryListQuery;
use Modules\Category\Presentation\Requests\CreateCategoryRequest;
use Modules\Category\Presentation\Requests\UpdateCategoryRequest;
use Modules\Category\Presentation\Resources\CategoryResource;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly GetCategoryListHandler $listHandler,
        private readonly GetCategoryByIdHandler $byIdHandler,
        private readonly CreateCategoryHandler  $createHandler,
        private readonly UpdateCategoryHandler  $updateHandler,
        private readonly DeleteCategoryHandler  $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->listHandler->handle(
            GetCategoryListQuery::fromRequest($request)
        );

        return CategoryResource::collection($categories)->response();
    }

    public function show(int $category): JsonResponse
    {
        $result = $this->byIdHandler->handle(new GetCategoryByIdQuery($category));

        return CategoryResource::make($result)->response();
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $result = $this->createHandler->handle(
            CreateCategoryCommand::fromRequest($request)
        );

        return CategoryResource::make($result)
            ->additional(['message' => 'Kategoriya yaratildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $result = $this->updateHandler->handle(
            UpdateCategoryCommand::fromRequest($request, $category)
        );

        return CategoryResource::make($result)
            ->additional(['message' => 'Kategoriya yangilandi'])
            ->response();
    }

    public function destroy(int $category): \Illuminate\Http\Response
    {
        $this->deleteHandler->handle(new DeleteCategoryCommand($category));

        return response()->noContent();
    }
}
