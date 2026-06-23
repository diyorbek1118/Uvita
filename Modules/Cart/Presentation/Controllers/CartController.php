<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\Application\Commands\AddItemCommand;
use Modules\Cart\Application\Commands\ClearCartCommand;
use Modules\Cart\Application\Commands\RemoveItemCommand;
use Modules\Cart\Application\Handlers\AddItemHandler;
use Modules\Cart\Application\Handlers\ClearCartHandler;
use Modules\Cart\Application\Handlers\GetCartHandler;
use Modules\Cart\Application\Handlers\RemoveItemHandler;
use Modules\Cart\Application\Queries\GetCartQuery;
use Modules\Cart\Presentation\Requests\AddItemRequest;
use Modules\Cart\Presentation\Requests\RemoveItemRequest;
use Modules\Cart\Presentation\Resources\CartResource;

final class CartController extends Controller
{
    public function __construct(
        private readonly GetCartHandler    $getCartHandler,
        private readonly AddItemHandler    $addItemHandler,
        private readonly RemoveItemHandler $removeItemHandler,
        private readonly ClearCartHandler  $clearCartHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getCartHandler->handle(
            new GetCartQuery(auth()->id())
        );

        return CartResource::make($cart)->response();
    }

    public function add(AddItemRequest $request): JsonResponse
    {
        $cart = $this->addItemHandler->handle(
            AddItemCommand::fromRequest($request, auth()->id())
        );

        return CartResource::make($cart)
            ->additional(['message' => "Mahsulot savatchaga qo'shildi"])
            ->response();
    }

    public function remove(RemoveItemRequest $request): JsonResponse
    {
        $cart = $this->removeItemHandler->handle(
            RemoveItemCommand::fromRequest($request, auth()->id())
        );

        return CartResource::make($cart)
            ->additional(['message' => 'Mahsulot savatchadan olib tashlandi'])
            ->response();
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->clearCartHandler->handle(
            new ClearCartCommand(auth()->id())
        );

        return CartResource::make($cart)
            ->additional(['message' => 'Savatcha tozalandi'])
            ->response();
    }
}
