<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Courier\Application\Commands\CreateSupportTicketCommand;
use Modules\Courier\Application\Commands\RegisterCourierDeviceCommand;
use Modules\Courier\Application\Commands\SaveCourierLocationCommand;
use Modules\Courier\Application\Commands\SetCourierAvailabilityCommand;
use Modules\Courier\Application\Commands\UpdateCourierProfileCommand;
use Modules\Courier\Application\Handlers\CreateCourierSupportTicketHandler;
use Modules\Courier\Application\Handlers\GetCourierEarningsHandler;
use Modules\Courier\Application\Handlers\GetCourierHistoryHandler;
use Modules\Courier\Application\Handlers\GetCourierNotificationsHandler;
use Modules\Courier\Application\Handlers\GetCourierProfileHandler;
use Modules\Courier\Application\Handlers\GetCourierStatsHandler;
use Modules\Courier\Application\Handlers\GetCourierSupportTicketsHandler;
use Modules\Courier\Application\Handlers\MarkCourierNotificationReadHandler;
use Modules\Courier\Application\Handlers\RegisterCourierDeviceHandler;
use Modules\Courier\Application\Handlers\RemoveCourierDeviceHandler;
use Modules\Courier\Application\Handlers\SaveCourierLocationHandler;
use Modules\Courier\Application\Handlers\SetCourierAvailabilityHandler;
use Modules\Courier\Application\Handlers\UpdateCourierProfileHandler;
use Modules\Courier\Application\Queries\GetCourierHistoryQuery;
use Modules\Courier\Application\Queries\GetCourierProfileQuery;
use Modules\Courier\Application\Queries\GetCourierStatsQuery;
use Modules\Courier\Presentation\Requests\CreateSupportTicketRequest;
use Modules\Courier\Presentation\Requests\RegisterCourierDeviceRequest;
use Modules\Courier\Presentation\Requests\SaveCourierLocationRequest;
use Modules\Courier\Presentation\Requests\SetCourierAvailabilityRequest;
use Modules\Courier\Presentation\Requests\UpdateCourierProfileRequest;
use Modules\Courier\Presentation\Resources\CourierLocationResource;
use Modules\Courier\Presentation\Resources\CourierNotificationResource;
use Modules\Courier\Presentation\Resources\CourierOrderResource;
use Modules\Courier\Presentation\Resources\CourierProfileResource;
use Modules\Courier\Presentation\Resources\CourierStatsResource;
use Modules\Courier\Presentation\Resources\CourierSupportTicketResource;

final class CourierController extends Controller
{
    public function __construct(
        private readonly GetCourierProfileHandler $profileHandler,
        private readonly GetCourierHistoryHandler $historyHandler,
        private readonly GetCourierStatsHandler $statsHandler,
        private readonly UpdateCourierProfileHandler $updateProfileHandler,
        private readonly SetCourierAvailabilityHandler $availabilityHandler,
        private readonly RegisterCourierDeviceHandler $registerDeviceHandler,
        private readonly RemoveCourierDeviceHandler $removeDeviceHandler,
        private readonly GetCourierNotificationsHandler $notificationsHandler,
        private readonly MarkCourierNotificationReadHandler $readNotificationHandler,
        private readonly SaveCourierLocationHandler $saveLocationHandler,
        private readonly GetCourierEarningsHandler $earningsHandler,
        private readonly CreateCourierSupportTicketHandler $createSupportHandler,
        private readonly GetCourierSupportTicketsHandler $supportHandler,
    ) {}

    public function profile(): JsonResponse
    {
        $courier = $this->profileHandler->handle(
            new GetCourierProfileQuery(auth('sanctum')->id())
        );

        return CourierProfileResource::make($courier)->response();
    }

    public function history(): JsonResponse
    {
        $orders = $this->historyHandler->handle(
            new GetCourierHistoryQuery(auth('sanctum')->id())
        );

        return CourierOrderResource::collection($orders)->response();
    }

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle(
            new GetCourierStatsQuery(auth('sanctum')->id())
        );

        return response()->json(['data' => (new CourierStatsResource($stats))->toArray(request())]);
    }

    public function updateProfile(UpdateCourierProfileRequest $request): JsonResponse
    {
        $courier = $this->updateProfileHandler->handle(new UpdateCourierProfileCommand(
            courierId: auth('sanctum')->id(),
            name: $request->string('name')->toString(),
            phone: $request->input('phone'),
            vehicleType: $request->input('vehicle_type'),
            vehicleNumber: $request->input('vehicle_number'),
            photo: $request->input('photo'),
        ));

        return CourierProfileResource::make($courier)
            ->additional(['message' => 'Profil yangilandi'])
            ->response();
    }

    public function availability(SetCourierAvailabilityRequest $request): JsonResponse
    {
        $profile = $this->availabilityHandler->handle(new SetCourierAvailabilityCommand(
            courierId: auth('sanctum')->id(),
            isOnline: $request->boolean('is_online'),
        ));

        return response()->json(['data' => [
            'is_online' => $profile->is_online,
            'shift_started_at' => $profile->shift_started_at?->toISOString(),
            'shift_ended_at' => $profile->shift_ended_at?->toISOString(),
        ]]);
    }

    public function registerDevice(RegisterCourierDeviceRequest $request): JsonResponse
    {
        $device = $this->registerDeviceHandler->handle(new RegisterCourierDeviceCommand(
            courierId: auth('sanctum')->id(),
            token: $request->string('token')->toString(),
            platform: $request->string('platform')->toString(),
            deviceName: $request->input('device_name'),
        ));

        return response()->json(['data' => ['id' => $device->id]], 201);
    }

    public function removeDevice(int $id): Response
    {
        $this->removeDeviceHandler->handle($id, auth('sanctum')->id());

        return response()->noContent();
    }

    public function notifications(): JsonResponse
    {
        return CourierNotificationResource::collection(
            $this->notificationsHandler->handle(auth('sanctum')->id())
        )->response();
    }

    public function readNotification(int $id): JsonResponse
    {
        $notification = $this->readNotificationHandler->handle($id, auth('sanctum')->id());

        return response()->json([
            'data' => (new CourierNotificationResource($notification))->toArray(request()),
        ]);
    }

    public function saveLocation(SaveCourierLocationRequest $request): JsonResponse
    {
        $location = $this->saveLocationHandler->handle(new SaveCourierLocationCommand(
            courierId: auth('sanctum')->id(),
            orderId: $request->integer('order_id'),
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
            accuracy: $request->filled('accuracy') ? $request->integer('accuracy') : null,
            recordedAt: $request->input('recorded_at'),
        ));

        return CourierLocationResource::make($location)->response()->setStatusCode(201);
    }

    public function earnings(): JsonResponse
    {
        return response()->json(['data' => $this->earningsHandler->handle(auth('sanctum')->id())]);
    }

    public function support(): JsonResponse
    {
        return CourierSupportTicketResource::collection(
            $this->supportHandler->handle(auth('sanctum')->id())
        )->response();
    }

    public function createSupport(CreateSupportTicketRequest $request): JsonResponse
    {
        $ticket = $this->createSupportHandler->handle(new CreateSupportTicketCommand(
            courierId: auth('sanctum')->id(),
            orderId: $request->filled('order_id') ? $request->integer('order_id') : null,
            category: $request->string('category')->toString(),
            message: $request->string('message')->toString(),
        ));

        return CourierSupportTicketResource::make($ticket)->response()->setStatusCode(201);
    }
}
