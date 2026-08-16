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
use Modules\Courier\Application\Services\CourierTripManager;
use Modules\Courier\Application\Services\TripBundlePlanner;
use Modules\Courier\Presentation\Requests\CancelCourierTripRequest;
use Modules\Courier\Presentation\Requests\CompleteTripDeliveryRequest;
use Modules\Courier\Presentation\Requests\CreateSupportTicketRequest;
use Modules\Courier\Presentation\Requests\PlanCourierTripRequest;
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
use Modules\Courier\Presentation\Resources\CourierTripResource;

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
        private readonly TripBundlePlanner $tripPlanner,
        private readonly CourierTripManager $tripManager,
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
            vehicleCapacityKg: $request->filled('vehicle_capacity_kg') ? $request->float('vehicle_capacity_kg') : null,
            maxOrdersPerTrip: $request->filled('max_orders_per_trip') ? $request->integer('max_orders_per_trip') : null,
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

    public function routes(): JsonResponse
    {
        return response()->json(['data' => $this->tripPlanner->availableRoutes()]);
    }

    public function previewTrip(PlanCourierTripRequest $request): JsonResponse
    {
        $plan = $this->tripManager->preview(
            auth('sanctum')->id(),
            $request->string('route_key')->toString(),
            $request->filled('capacity_kg') ? $request->float('capacity_kg') : null,
        );

        return response()->json(['data' => [
            'route' => $plan['route'],
            'capacity_kg' => $plan['capacity_kg'],
            'max_orders' => $plan['max_orders'],
            'orders_count' => $plan['orders']->count(),
            'total_weight_kg' => $plan['total_weight_kg'],
            'cargo_value' => $plan['cargo_value'],
            'total_courier_fee' => $plan['total_courier_fee'],
            'pickup_points_count' => count($plan['pickup_sequences']),
            'customer_addresses_revealed' => false,
        ]]);
    }

    public function createTrip(PlanCourierTripRequest $request): JsonResponse
    {
        $trip = $this->tripManager->create(
            auth('sanctum')->id(),
            $request->string('route_key')->toString(),
            $request->filled('capacity_kg') ? $request->float('capacity_kg') : null,
        );

        return CourierTripResource::make($trip)
            ->additional(['message' => 'Reys avtomatik shakllantirildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function activeTrip(): JsonResponse
    {
        $trip = $this->tripManager->active(auth('sanctum')->id());

        return $trip === null
            ? response()->json(['data' => null])
            : CourierTripResource::make($trip)->response();
    }

    public function completePickup(int $trip, string $pickupKey): JsonResponse
    {
        $updated = $this->tripManager->completePickup($trip, $pickupKey, auth('sanctum')->id());

        return CourierTripResource::make($updated)
            ->additional(['message' => $updated->pickups_completed_at !== null
                ? 'Barcha yuklar olindi'
                : 'Yuk olindi'])
            ->response();
    }

    public function completeTripDelivery(int $trip, int $order, CompleteTripDeliveryRequest $request): JsonResponse
    {
        $updated = $this->tripManager->completeDelivery(
            $trip,
            $order,
            auth('sanctum')->id(),
            $request->string('pin')->toString(),
            $request->integer('cash_received'),
            $request->input('recipient_name'),
            $request->filled('latitude') ? $request->float('latitude') : null,
            $request->filled('longitude') ? $request->float('longitude') : null,
        );

        return CourierTripResource::make($updated)
            ->additional(['message' => $updated->completed_at !== null
                ? 'Barcha buyurtmalar yetkazildi'
                : 'Buyurtma yetkazildi'])
            ->response();
    }

    public function cancelTrip(int $trip, CancelCourierTripRequest $request): JsonResponse
    {
        $cancelled = $this->tripManager->cancel(
            $trip,
            auth('sanctum')->id(),
            $request->string('reason')->toString(),
        );

        return CourierTripResource::make($cancelled)
            ->additional(['message' => 'Reys bekor qilindi, yuklar boshqa kuryerlarga qaytarildi'])
            ->response();
    }
}
