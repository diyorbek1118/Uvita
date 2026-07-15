<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Order\Application\Commands\AssignCourierCommand;
use Modules\Order\Application\Commands\CancelOrderCommand;
use Modules\Order\Application\Commands\ConfirmOrderCommand;
use Modules\Order\Application\Commands\CreateOrderCommand;
use Modules\Order\Application\Commands\DeliveryIssueResolveCommand;
use Modules\Order\Application\Commands\MarkDeliveredCommand;
use Modules\Order\Application\Commands\MarkDeliveringCommand;
use Modules\Order\Application\Commands\NotFoundCommand;
use Modules\Order\Application\Commands\ReadyToDeliverCommand;
use Modules\Order\Application\Handlers\AssignCourierHandler;
use Modules\Order\Application\Handlers\CancelOrderHandler;
use Modules\Order\Application\Handlers\ConfirmOrderHandler;
use Modules\Order\Application\Handlers\CreateOrderHandler;
use Modules\Order\Application\Handlers\GetAdminOrdersHandler;
use Modules\Order\Application\Handlers\GetAnyOrderByIdHandler;
use Modules\Order\Application\Handlers\GetCourierOrderByIdHandler;
use Modules\Order\Application\Handlers\GetCourierOrdersHandler;
use Modules\Order\Application\Handlers\GetMyOrdersHandler;
use Modules\Order\Application\Handlers\GetOrderByIdHandler;
use Modules\Order\Application\Handlers\GetPaidOrdersHandler;
use Modules\Order\Application\Handlers\MarkDeliveredHandler;
use Modules\Order\Application\Handlers\MarkDeliveringHandler;
use Modules\Order\Application\Handlers\NotFoundHandler;
use Modules\Order\Application\Handlers\ReadyToDeliverHandler;
use Modules\Order\Application\Handlers\ResolveIssueHandler;
use Modules\Order\Application\Queries\GetAdminOrdersQuery;
use Modules\Order\Application\Queries\GetCourierOrdersQuery;
use Modules\Order\Application\Queries\GetMyOrdersQuery;
use Modules\Order\Application\Queries\GetOrderByIdQuery;
use Modules\Order\Application\Queries\GetPaidOrdersQuery;
use Modules\Order\Presentation\Requests\AssignCourierRequest;
use Modules\Order\Presentation\Requests\CreateOrderRequest;
use Modules\Order\Presentation\Requests\NotFoundRequest;
use Modules\Order\Presentation\Requests\ResolveIssueRequest;
use Modules\Order\Presentation\Resources\OrderResource;
use Modules\Payment\Application\Commands\CreatePaymentCommand;
use Modules\Payment\Application\Handlers\CreatePaymentHandler;

final class OrderController extends Controller
{
    public function __construct(
        private readonly CreateOrderHandler        $createHandler,
        private readonly GetOrderByIdHandler       $getByIdHandler,
        private readonly GetMyOrdersHandler        $getMyOrdersHandler,
        private readonly GetPaidOrdersHandler      $getPaidOrdersHandler,
        private readonly GetAnyOrderByIdHandler    $getAnyByIdHandler,
        private readonly GetAdminOrdersHandler     $getAdminOrdersHandler,
        private readonly GetCourierOrdersHandler   $getCourierOrdersHandler,
        private readonly GetCourierOrderByIdHandler $getCourierOrderByIdHandler,
        private readonly ConfirmOrderHandler       $confirmHandler,
        private readonly ReadyToDeliverHandler     $readyToDeliverHandler,
        private readonly AssignCourierHandler      $assignCourierHandler,
        private readonly ResolveIssueHandler       $resolveIssueHandler,
        private readonly MarkDeliveringHandler     $markDeliveringHandler,
        private readonly MarkDeliveredHandler      $markDeliveredHandler,
        private readonly NotFoundHandler           $notFoundHandler,
        private readonly CancelOrderHandler        $cancelHandler,
        private readonly CreatePaymentHandler      $createPaymentHandler,
    ) {}

    // ─── Customer ────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $orders = $this->getMyOrdersHandler->handle(
            new GetMyOrdersQuery(auth()->id())
        );
        return OrderResource::collection($orders)->response();
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->getByIdHandler->handle(
            new GetOrderByIdQuery(orderId: $id, userId: auth()->id())
        );
        return OrderResource::make($order)->response();
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->createHandler->handle(
            CreateOrderCommand::fromRequest($request, auth()->id())
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma yaratildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(int $id): JsonResponse
    {
        $order = $this->cancelHandler->handle(
            new CancelOrderCommand(orderId: $id, userId: auth()->id())
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma bekor qilindi'])
            ->response();
    }

    public function payRetry(int $id): JsonResponse
    {
        $order = $this->getByIdHandler->handle(
            new GetOrderByIdQuery(orderId: $id, userId: auth()->id())
        );

        $provider = (string) request('provider', 'payme');

        $result = $this->createPaymentHandler->handle(new CreatePaymentCommand(
            orderId:  $id,
            provider: $provider,
        ));

        return response()->json([
            'data'        => ['payment_url' => $result['payment_url'] ?? null],
            'message'     => "To'lov URL yaratildi",
        ]);
    }

    // ─── Manager ─────────────────────────────────────────────────────────────

    public function paidOrders(): JsonResponse
    {
        $orders = $this->getPaidOrdersHandler->handle(new GetPaidOrdersQuery());
        return OrderResource::collection($orders)->response();
    }

    public function managerShow(int $id): JsonResponse
    {
        $order = $this->getAnyByIdHandler->handle($id);
        return OrderResource::make($order)->response();
    }

    public function confirm(int $id): JsonResponse
    {
        $order = $this->confirmHandler->handle(new ConfirmOrderCommand($id));
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma tasdiqlandi'])
            ->response();
    }

    public function readyToDeliver(int $id): JsonResponse
    {
        $order = $this->readyToDeliverHandler->handle(
            new ReadyToDeliverCommand(orderId: $id, courierNote: request('courier_note'))
        );
        return OrderResource::make($order)
            ->additional(['message' => "Buyurtma yig'ildi, kuryerga topshirishga tayyor"])
            ->response();
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    public function adminOrders(): JsonResponse
    {
        $orders = $this->getAdminOrdersHandler->handle(new GetAdminOrdersQuery());
        return OrderResource::collection($orders)->response();
    }

    public function adminShow(int $id): JsonResponse
    {
        $order = $this->getAnyByIdHandler->handle($id);
        return OrderResource::make($order)->response();
    }

    public function assignCourier(int $id, AssignCourierRequest $request): JsonResponse
    {
        $order = $this->assignCourierHandler->handle(
            new AssignCourierCommand(orderId: $id, courierId: $request->integer('courier_id'))
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Kuryer tayinlandi'])
            ->response();
    }

    public function resolveIssue(int $id, ResolveIssueRequest $request): JsonResponse
    {
        $order = $this->resolveIssueHandler->handle(
            new DeliveryIssueResolveCommand(orderId: $id, action: $request->input('action'))
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Muammo hal qilindi'])
            ->response();
    }

    // ─── Courier ─────────────────────────────────────────────────────────────

    public function courierOrders(): JsonResponse
    {
        $orders = $this->getCourierOrdersHandler->handle(
            new GetCourierOrdersQuery(auth()->id())
        );
        return OrderResource::collection($orders)->response();
    }

    public function courierShow(int $id): JsonResponse
    {
        $order = $this->getCourierOrderByIdHandler->handle($id, auth()->id());
        return OrderResource::make($order)->response();
    }

    public function accept(int $id): JsonResponse
    {
        $order = $this->markDeliveringHandler->handle(
            new MarkDeliveringCommand(orderId: $id, courierId: auth()->id())
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma qabul qilindi, yetkazish boshlandi'])
            ->response();
    }

    public function markDelivered(int $id): JsonResponse
    {
        $order = $this->markDeliveredHandler->handle(new MarkDeliveredCommand($id));
        return OrderResource::make($order)
            ->additional(['message' => 'Buyurtma yetkazildi'])
            ->response();
    }

    public function notFound(int $id, NotFoundRequest $request): JsonResponse
    {
        $order = $this->notFoundHandler->handle(
            new NotFoundCommand(orderId: $id, reason: $request->input('reason'))
        );
        return OrderResource::make($order)
            ->additional(['message' => 'Topilmadi deb belgilandi'])
            ->response();
    }
}
