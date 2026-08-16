<?php

declare(strict_types=1);

namespace Tests\Feature\Courier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTrip;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

final class CourierPanelTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();
        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Test Customer']);
    }

    private function staff(StaffRole $role, string $suffix, bool $active = true): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function as(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('courier-test')->plainTextToken);
    }

    private function deliveryPin(OrderModel $order): string
    {
        $service = app(DeliveryConfirmationService::class);

        return $service->reveal($service->ensureForOrder($order->id));
    }

    private function order(?Staff $courier, string $status, int $notFoundCount = 0): OrderModel
    {
        return OrderModel::create([
            'user_id' => $this->customer->id,
            'courier_id' => $courier?->id,
            'status' => $status,
            'address' => ['region' => 'Toshkent', 'district' => 'Yunusobod', 'street' => 'Navoiy', 'house' => '1'],
            'phone' => $this->customer->phone,
            'phone_secondary' => '+998909999999',
            'delivery_time' => 'Bugun 14:00',
            'courier_note' => '2 ta paket',
            'total_price' => 100000,
            'service_fee' => 15000,
            'courier_fee' => 10000,
            'grand_total' => 115000,
            'not_found_count' => $notFoundCount,
        ]);
    }

    private function routeOrder(string $origin, string $destination, int $quantity = 10): OrderModel
    {
        $order = $this->order(null, 'ready_to_deliver');
        $order->update([
            'address' => [
                'region' => $destination,
                'district' => 'Markaziy tuman',
                'street' => 'Navoiy',
                'house' => '1',
            ],
            'ready_at' => now(),
        ]);
        $category = Category::firstOrCreate(['slug' => 'courier-load'], ['name' => 'Kuryer yuki']);
        $product = Product::create([
            'name' => "Yuk {$order->id}",
            'slug' => "courier-load-{$order->id}",
            'description' => 'Yetkazish uchun test yuki',
            'price' => 10000,
            'stock' => 1000,
            'status' => 'active',
            'images' => [],
            'category_id' => $category->id,
            'origin_region' => $origin,
            'unit' => 'kg',
            'minimum_order_quantity' => 1,
        ]);
        OrderItemModel::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
        ]);

        return $order;
    }

    public function test_courier_profile_and_permissions_are_scoped(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'profile');

        $this->as($courier)
            ->getJson('/api/courier/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', $courier->name)
            ->assertJsonPath('data.role', 'courier')
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/dashboard/products')->assertForbidden();
        $this->getJson('/api/admin/couriers')->assertForbidden();
        $this->getJson('/api/super/settings')->assertForbidden();
    }

    public function test_inactive_courier_is_blocked_even_with_existing_token(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'inactive', false);

        $this->as($courier)
            ->getJson('/api/courier/profile')
            ->assertForbidden();
    }

    public function test_active_orders_show_only_assigned_ready_and_delivering_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'owner');
        $other = $this->staff(StaffRole::COURIER, 'other');
        $ready = $this->order($courier, 'ready_to_deliver');
        $delivering = $this->order($courier, 'delivering');
        $delivered = $this->order($courier, 'delivered');
        $foreign = $this->order($other, 'ready_to_deliver');

        $this->as($courier)
            ->getJson('/api/courier/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $ready->id])
            ->assertJsonFragment(['id' => $delivering->id])
            ->assertJsonMissing(['id' => $delivered->id])
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->getJson("/api/courier/orders/{$ready->id}")->assertOk();
        $this->getJson("/api/courier/orders/{$foreign->id}")->assertNotFound();
    }

    public function test_available_routes_group_unassigned_orders_by_origin_and_destination(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'route-list');
        $first = $this->routeOrder('Jizzax', 'Toshkent', 20);
        $second = $this->routeOrder('Jizzax', 'Toshkent', 30);
        $assigned = $this->routeOrder('Jizzax', 'Toshkent', 40);
        $assigned->update(['courier_id' => $courier->id]);

        $this->as($courier)
            ->getJson('/api/courier/routes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.origin_region', 'Jizzax')
            ->assertJsonPath('data.0.destination_region', 'Toshkent')
            ->assertJsonPath('data.0.orders_count', 2)
            ->assertJsonPath('data.0.load_by_unit.kg', 50)
            ->assertJsonPath('data.0.orders.0.id', $first->id)
            ->assertJsonPath('data.0.orders.1.id', $second->id)
            ->assertJsonMissing(['id' => $assigned->id]);
    }

    public function test_courier_cannot_cherry_pick_and_profile_limit_controls_trip_size(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'batch-accept');
        $first = $this->routeOrder('Jizzax', 'Toshkent', 20);
        $second = $this->routeOrder('Jizzax', 'Toshkent', 30);
        $leftForAnotherCourier = $this->routeOrder('Jizzax', 'Toshkent', 40);

        CourierProfile::create([
            'courier_id' => $courier->id,
            'vehicle_capacity_kg' => 100,
            'max_orders_per_trip' => 2,
        ]);
        $this->as($courier)
            ->putJson('/api/courier/routes/accept', ['order_ids' => [$second->id]])
            ->assertNotFound();
        $this->postJson('/api/courier/trips', ['route_key' => 'jizzax|toshkent'])
            ->assertCreated()
            ->assertJsonPath('data.orders_count', 2);

        foreach ([$first, $second] as $accepted) {
            $this->assertDatabaseHas('orders', [
                'id' => $accepted->id,
                'courier_id' => $courier->id,
                'status' => 'ready_to_deliver',
            ]);
            $this->assertDatabaseHas('delivery_assignments', [
                'order_id' => $accepted->id,
                'courier_id' => $courier->id,
                'status' => 'accepted',
            ]);
        }
        $this->assertNull($leftForAnotherCourier->fresh()->courier_id);

        $this->getJson('/api/courier/orders')->assertJsonCount(2, 'data');
        $this->getJson('/api/courier/routes')->assertJsonPath('data.0.orders_count', 1);
    }

    public function test_automatic_trip_never_mixes_routes(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'mixed-routes');
        $jizzax = $this->routeOrder('Jizzax', 'Toshkent');
        $sirdaryo = $this->routeOrder('Sirdaryo', 'Toshkent');

        $this->as($courier)
            ->postJson('/api/courier/trips', ['route_key' => 'jizzax|toshkent'])
            ->assertCreated()
            ->assertJsonPath('data.orders_count', 1);

        $this->assertSame($courier->id, $jizzax->fresh()->courier_id);
        $this->assertNull($sirdaryo->fresh()->courier_id);
        $this->assertSame(1, DeliveryAssignment::count());
    }

    public function test_self_selected_order_starts_delivery_only_after_courier_confirms_pickup(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'pickup-start');
        $order = $this->routeOrder('Jizzax', 'Toshkent');

        $created = $this->as($courier)
            ->postJson('/api/courier/trips', ['route_key' => 'jizzax|toshkent'])
            ->assertCreated();
        $this->assertSame('ready_to_deliver', $order->fresh()->status->value);

        $this->putJson("/api/courier/trips/{$created->json('data.id')}/pickups/{$created->json('data.pickup_points.0.key')}")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering');

        $this->assertSame(1, DeliveryAssignment::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => $order->id,
            'status' => 'accepted',
        ]);
    }

    public function test_courier_can_return_self_selected_order_within_five_hours(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'cancel-accepted');
        $order = $this->routeOrder('Jizzax', 'Toshkent');
        $created = $this->as($courier)
            ->postJson('/api/courier/trips', ['route_key' => 'jizzax|toshkent'])
            ->assertCreated();

        $this->putJson("/api/courier/trips/{$created->json('data.id')}/cancel", ['reason' => 'Yuk mashinaga sig‘madi'])
            ->assertOk();

        $this->assertNull($order->fresh()->courier_id);
        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->getJson('/api/courier/routes')->assertJsonPath('data.0.orders.0.id', $order->id);
    }

    public function test_courier_cannot_return_accepted_order_after_five_hours(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'cancel-expired');
        $order = $this->routeOrder('Jizzax', 'Toshkent');
        $created = $this->as($courier)
            ->postJson('/api/courier/trips', ['route_key' => 'jizzax|toshkent'])
            ->assertCreated();
        CourierTrip::whereKey($created->json('data.id'))->update(['accepted_at' => now()->subHours(6)]);

        $this->putJson("/api/courier/trips/{$created->json('data.id')}/cancel", ['reason' => 'Kech bekor qilish'])
            ->assertUnprocessable();

        $this->assertSame($courier->id, $order->fresh()->courier_id);
    }

    public function test_history_contains_only_own_delivered_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'history');
        $other = $this->staff(StaffRole::COURIER, 'history-other');
        $own = $this->order($courier, 'delivered');
        $this->order($courier, 'delivering');
        $foreign = $this->order($other, 'delivered');
        $own->forceFill(['delivered_at' => now()])->saveQuietly();

        $this->as($courier)
            ->getJson('/api/courier/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.delivered_at', $own->fresh()->delivered_at?->toISOString())
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_only_assigned_courier_can_accept_ready_order(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'accept-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'accept-attacker');
        $order = $this->order($owner, 'ready_to_deliver');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => $owner->id, 'status' => 'ready_to_deliver']);

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering');

        $updated = $order->fresh();
        $this->assertSame($owner->id, $updated->courier_id);
        $this->assertNotNull($updated->delivering_at);
    }

    public function test_accept_rejects_wrong_status_including_delivery_issue(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'accept-status');

        foreach (['confirmed', 'delivering', 'delivered', 'delivery_issue'] as $status) {
            $order = $this->order($courier, $status);
            $this->as($courier)
                ->putJson("/api/courier/orders/{$order->id}/accept")
                ->assertUnprocessable();
        }
    }

    public function test_only_assigned_courier_can_mark_delivered(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'deliver-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'deliver-attacker');
        $order = $this->order($owner, 'delivering');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/delivered")
            ->assertNotFound();

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/delivered", [
                'pin' => $this->deliveryPin($order),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertNotNull($order->fresh()->delivered_at);
        $this->getJson('/api/courier/orders')->assertJsonCount(0, 'data');
        $this->getJson('/api/courier/history')->assertJsonFragment(['id' => $order->id]);
    }

    public function test_delivered_action_rejects_non_delivering_status(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'deliver-status');
        $order = $this->order($courier, 'ready_to_deliver');

        $this->as($courier)
            ->putJson("/api/courier/orders/{$order->id}/delivered")
            ->assertUnprocessable();
    }

    public function test_not_found_requires_reason_and_only_owner_can_increment(): void
    {
        $owner = $this->staff(StaffRole::COURIER, 'not-found-owner');
        $attacker = $this->staff(StaffRole::COURIER, 'not-found-attacker');
        $order = $this->order($owner, 'delivering');

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/not-found", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->as($attacker)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Telefonni olmadi'])
            ->assertNotFound();

        $this->as($owner)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Telefonni olmadi'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering')
            ->assertJsonPath('data.not_found_count', 1);
    }

    public function test_third_not_found_attempt_creates_delivery_issue_and_stops_more_attempts(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'third-attempt');
        $order = $this->order($courier, 'delivering', 2);

        $this->as($courier)
            ->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'Uchinchi urinish'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivery_issue')
            ->assertJsonPath('data.not_found_count', 3);

        $updated = $order->fresh();
        $this->assertNotNull($updated->delivery_issue_at);

        $this->putJson("/api/courier/orders/{$order->id}/not-found", ['reason' => 'To‘rtinchi urinish'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'not_found_count' => 3, 'status' => 'delivery_issue']);
        $this->getJson('/api/courier/orders')->assertJsonCount(0, 'data');
    }

    public function test_stats_use_only_current_courier_orders(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'stats');
        $other = $this->staff(StaffRole::COURIER, 'stats-other');
        $this->order($courier, 'delivered', 1);
        $this->order($courier, 'delivered', 2);
        $this->order($courier, 'ready_to_deliver');
        $this->order($courier, 'delivering');
        $this->order($other, 'delivered', 10);

        $this->as($courier)
            ->getJson('/api/courier/stats')
            ->assertOk()
            ->assertJsonPath('data.total_delivered', 2)
            ->assertJsonPath('data.total_not_found', 3)
            ->assertJsonPath('data.total_active', 2)
            ->assertJsonPath('data.success_rate', 40);
    }

    public function test_trip_is_selected_automatically_and_addresses_are_hidden_until_all_pickups(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'trip-auto');
        CourierProfile::create([
            'courier_id' => $courier->id,
            'vehicle_capacity_kg' => 25,
            'max_orders_per_trip' => 2,
        ]);
        $oldest = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $second = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $left = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $oldest->forceFill(['created_at' => now()->subHours(3)])->saveQuietly();

        $response = $this->as($courier)
            ->postJson('/api/courier/trips', [
                'route_key' => 'jizzax|toshkent',
                'capacity_kg' => 25,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'picking_up')
            ->assertJsonPath('data.orders_count', 2)
            ->assertJsonPath('data.customer_addresses_revealed', false)
            ->assertJsonMissingPath('data.deliveries.0.address');

        $tripId = $response->json('data.id');
        $this->assertNotNull($oldest->fresh()->courier_id);
        $this->assertNotNull($second->fresh()->courier_id);
        $this->assertNull($left->fresh()->courier_id);

        $pickups = $response->json('data.pickup_points');
        $this->putJson("/api/courier/trips/{$tripId}/pickups/{$pickups[0]['key']}")
            ->assertOk()
            ->assertJsonPath('data.status', 'picking_up')
            ->assertJsonPath('data.customer_addresses_revealed', false);
        $this->putJson("/api/courier/trips/{$tripId}/pickups/{$pickups[1]['key']}")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivering')
            ->assertJsonPath('data.customer_addresses_revealed', true)
            ->assertJsonPath('data.deliveries.0.address.region', 'Toshkent');
    }

    public function test_trip_cash_delivery_finishes_with_platform_handover_summary(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'trip-cash');
        $order = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $created = $this->as($courier)->postJson('/api/courier/trips', [
            'route_key' => 'jizzax|toshkent',
        ])->assertCreated();
        $tripId = $created->json('data.id');
        $pickupKey = $created->json('data.pickup_points.0.key');
        $this->putJson("/api/courier/trips/{$tripId}/pickups/{$pickupKey}")->assertOk();

        $this->putJson("/api/courier/trips/{$tripId}/orders/{$order->id}/delivered", [
            'pin' => $this->deliveryPin($order),
            'cash_received' => 1,
        ])->assertUnprocessable();

        $this->putJson("/api/courier/trips/{$tripId}/orders/{$order->id}/delivered", [
            'pin' => $this->deliveryPin($order),
            'cash_received' => $order->grand_total,
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.cash_collected', $order->grand_total)
            ->assertJsonPath('data.platform_cash_due', $order->grand_total - $order->courier_fee);
    }

    public function test_trip_never_exceeds_fifty_million_cargo_value(): void
    {
        $courier = $this->staff(StaffRole::COURIER, 'trip-limit');
        $first = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $second = $this->routeOrder('Jizzax', 'Toshkent', 10);
        $first->update(['grand_total' => 30000000]);
        $second->update(['grand_total' => 30000000]);

        $this->as($courier)->postJson('/api/courier/trips', [
            'route_key' => 'jizzax|toshkent',
        ])->assertCreated()
            ->assertJsonPath('data.orders_count', 1)
            ->assertJsonPath('data.cargo_value', 30000000);
    }
}
