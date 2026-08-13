<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Courier\Application\Commands\CreateCourierPayoutCommand;
use Modules\Courier\Application\Commands\ResolveSupportTicketCommand;
use Modules\Courier\Application\Handlers\CreateCourierPayoutHandler;
use Modules\Courier\Application\Handlers\GetAdminCourierSupportTicketsHandler;
use Modules\Courier\Application\Handlers\GetCourierPayoutsHandler;
use Modules\Courier\Application\Handlers\GetLatestCourierLocationHandler;
use Modules\Courier\Application\Handlers\MarkCourierPayoutPaidHandler;
use Modules\Courier\Application\Handlers\ResolveCourierSupportTicketHandler;
use Modules\Courier\Presentation\Requests\CreateCourierPayoutRequest;
use Modules\Courier\Presentation\Requests\ResolveSupportTicketRequest;
use Modules\Courier\Presentation\Resources\CourierLocationResource;
use Modules\Courier\Presentation\Resources\CourierPayoutResource;
use Modules\Courier\Presentation\Resources\CourierSupportTicketResource;

final class AdminCourierOperationsController extends Controller
{
    public function __construct(
        private readonly GetAdminCourierSupportTicketsHandler $supportHandler,
        private readonly ResolveCourierSupportTicketHandler $resolveHandler,
        private readonly GetLatestCourierLocationHandler $locationHandler,
        private readonly CreateCourierPayoutHandler $createPayoutHandler,
        private readonly GetCourierPayoutsHandler $payoutsHandler,
        private readonly MarkCourierPayoutPaidHandler $markPayoutPaidHandler,
    ) {}

    public function support(): JsonResponse
    {
        $status = request()->string('status')->toString() ?: null;

        return CourierSupportTicketResource::collection($this->supportHandler->handle($status))->response();
    }

    public function resolveSupport(int $id, ResolveSupportTicketRequest $request): JsonResponse
    {
        $ticket = $this->resolveHandler->handle(new ResolveSupportTicketCommand(
            ticketId: $id,
            adminId: auth('sanctum')->id(),
            status: $request->string('status')->toString(),
            reply: $request->input('reply'),
        ));

        return CourierSupportTicketResource::make($ticket)->response();
    }

    public function latestLocation(int $id): JsonResponse
    {
        return CourierLocationResource::make($this->locationHandler->handle($id))->response();
    }

    public function payouts(): JsonResponse
    {
        $courierId = request()->filled('courier_id') ? request()->integer('courier_id') : null;
        $status = request()->string('status')->toString() ?: null;

        return CourierPayoutResource::collection($this->payoutsHandler->handle($courierId, $status))->response();
    }

    public function createPayout(CreateCourierPayoutRequest $request): JsonResponse
    {
        $payout = $this->createPayoutHandler->handle(new CreateCourierPayoutCommand(
            courierId: $request->integer('courier_id'),
            periodStart: $request->string('period_start')->toString(),
            periodEnd: $request->string('period_end')->toString(),
            note: $request->input('note'),
            createdBy: auth('sanctum')->id(),
        ));

        return CourierPayoutResource::make($payout)->response()->setStatusCode(201);
    }

    public function markPayoutPaid(int $id): JsonResponse
    {
        return CourierPayoutResource::make($this->markPayoutPaidHandler->handle($id))->response();
    }
}
