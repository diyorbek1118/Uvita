<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Shared\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

class AdminDashboardFinanceSettingsTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private function staff(StaffRole $role, string $suffix): Staff
    {
        return Staff::create([
            'name' => "{$role->value} {$suffix}",
            'email' => "{$role->value}-{$suffix}@uvita.uz",
            'password' => Hash::make('password123'),
            'role' => $role->value,
            'is_active' => true,
        ]);
    }

    private function as(Staff $staff): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($staff->createToken('test')->plainTextToken);
    }

    private function order(
        User $user,
        string $status,
        int $total,
        int $serviceFee,
        int $courierFee,
        string $date,
    ): OrderModel {
        $order = OrderModel::create([
            'user_id' => $user->id,
            'status' => $status,
            'address' => ['region' => 'T', 'district' => 'Y', 'street' => 'N', 'house' => '1'],
            'phone' => $user->phone,
            'delivery_time' => 'Ertaga',
            'total_price' => $total,
            'service_fee' => $serviceFee,
            'courier_fee' => $courierFee,
            'grand_total' => $total,
        ]);
        $order->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();

        return $order->fresh();
    }

    public function test_admin_dashboard_summary_and_status_counts_match_database(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'summary');
        $this->staff(StaffRole::COURIER, 'active');
        $inactiveCourier = $this->staff(StaffRole::COURIER, 'inactive');
        $inactiveCourier->update(['is_active' => false]);
        $customer = User::create(['phone' => '+998901111111', 'name' => 'Ali']);
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        Product::create([
            'name' => 'Active Low', 'slug' => 'active-low', 'description' => 'D',
            'price' => 10000, 'stock' => 3, 'status' => 'active', 'images' => [],
            'category_id' => $category->id,
        ]);
        Product::create([
            'name' => 'Pending Admin', 'slug' => 'pending-admin', 'description' => 'D',
            'price' => 10000, 'stock' => 20, 'status' => 'inactive', 'images' => [],
            'category_id' => $category->id, 'manager_id' => null,
        ]);
        $this->order($customer, 'pending', 100000, 15000, 10000, now()->format('Y-m-d H:i:s'));
        $this->order($customer, 'delivery_issue', 100000, 15000, 10000, now()->format('Y-m-d H:i:s'));

        $this->as($admin)
            ->getJson('/api/dashboard/analytics/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.orders.today', 2)
            ->assertJsonPath('data.orders.total', 2)
            ->assertJsonPath('data.pending_approvals', 1)
            ->assertJsonPath('data.delivery_issues', 1)
            ->assertJsonPath('data.active_couriers', 1)
            ->assertJsonPath('data.low_stock', 1)
            ->assertJsonPath('data.active_products', 1)
            ->assertJsonPath('data.total_customers', 1);

        $this->getJson('/api/dashboard/analytics/order-status')
            ->assertStatus(200)
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.delivery_issue', 1)
            ->assertJsonPath('data.total', 2);
    }

    public function test_top_products_uses_only_sold_order_statuses(): void
    {
        $admin = $this->staff(StaffRole::ADMIN, 'top');
        $customer = User::create(['phone' => '+998902222222', 'name' => 'Ali']);
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'name' => 'Top', 'slug' => 'top', 'description' => 'D',
            'price' => 10000, 'stock' => 10, 'status' => 'active', 'images' => [],
            'category_id' => $category->id,
        ]);
        $paid = $this->order($customer, 'paid', 30000, 4500, 5000, '2026-07-10 10:00:00');
        $pending = $this->order($customer, 'pending', 50000, 7500, 5000, '2026-07-10 11:00:00');
        OrderItemModel::create(['order_id' => $paid->id, 'product_id' => $product->id, 'quantity' => 3, 'price' => 10000]);
        OrderItemModel::create(['order_id' => $pending->id, 'product_id' => $product->id, 'quantity' => 5, 'price' => 10000]);

        $this->as($admin)
            ->getJson('/api/dashboard/analytics/top-products?limit=10')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.units_sold', 3)
            ->assertJsonPath('data.0.revenue', 30000);

        $this->getJson('/api/dashboard/analytics/top-products?limit=0')->assertStatus(422);
    }

    public function test_financial_analytics_sum_historical_stored_values_and_are_super_only(): void
    {
        $super = $this->staff(StaffRole::SUPER_ADMIN, 'finance');
        $admin = $this->staff(StaffRole::ADMIN, 'finance');
        $customer = User::create(['phone' => '+998903333333', 'name' => 'Ali']);
        $this->order($customer, 'paid', 100000, 10000, 5000, '2026-07-10 10:00:00');
        $this->order($customer, 'delivered', 200000, 20000, 10000, '2026-07-15 10:00:00');
        $this->order($customer, 'cancelled', 900000, 90000, 45000, '2026-07-15 12:00:00');

        $this->as($super)
            ->getJson('/api/dashboard/analytics/revenue?from=2026-07-01&to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonPath('data.orders_count', 2)
            ->assertJsonPath('data.gross_sales', 300000)
            ->assertJsonPath('data.seller_payouts', 243000)
            ->assertJsonPath('data.platform_fee_gross', 30000)
            ->assertJsonPath('data.courier_fees', 15000)
            ->assertJsonPath('data.platform_fee_net', 42000)
            ->assertJsonPath('data.customer_total', 300000);

        $this->getJson('/api/dashboard/analytics/sales?period=monthly')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.period', '2026-07')
            ->assertJsonPath('data.0.orders_count', 2)
            ->assertJsonPath('data.0.gross_sales', 300000);

        $this->getJson('/api/dashboard/analytics/sales?period=yearly')->assertStatus(422);

        $this->as($admin)->getJson('/api/dashboard/analytics/revenue')->assertStatus(403);
    }

    public function test_transactions_filters_stats_and_permissions(): void
    {
        $super = $this->staff(StaffRole::SUPER_ADMIN, 'transactions');
        $admin = $this->staff(StaffRole::ADMIN, 'transactions');
        $customer = User::create(['phone' => '+998904444444', 'name' => 'Ali']);
        $order = $this->order($customer, 'paid', 100000, 15000, 10000, '2026-07-10 10:00:00');
        $payme = PaymentModel::create([
            'order_id' => $order->id,
            'provider' => 'payme',
            'transaction_id' => 'tx-payme',
            'amount' => 11500000,
            'status' => 'paid',
        ]);
        $payme->forceFill(['created_at' => '2026-07-10 10:00:00'])->saveQuietly();
        PaymentModel::create([
            'order_id' => $order->id,
            'provider' => 'click',
            'transaction_id' => 'tx-click',
            'amount' => 11500000,
            'status' => 'failed',
        ]);

        $this->as($super)
            ->getJson('/api/super/transactions?provider=payme&status=paid&date_from=2026-07-01&date_to=2026-07-15')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_id', 'tx-payme');

        $this->getJson('/api/super/transactions/stats')
            ->assertStatus(200)
            ->assertJsonPath('data.total_count', 2)
            ->assertJsonPath('data.paid_count', 1)
            ->assertJsonPath('data.failed_count', 1)
            ->assertJsonPath('data.by_provider.payme.count', 1);

        $this->getJson('/api/super/transactions?provider=visa')->assertStatus(422);
        $this->as($admin)->getJson('/api/super/transactions')->assertStatus(403);
    }

    public function test_settings_update_validation_cache_and_permissions(): void
    {
        $this->seedSettings();
        $super = $this->staff(StaffRole::SUPER_ADMIN, 'settings');
        $admin = $this->staff(StaffRole::ADMIN, 'settings');

        $this->as($super)
            ->getJson('/api/super/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.delivery.min_order_amount', '50000');

        $this->putJson('/api/super/settings', [
            'key' => 'min_order_amount',
            'value' => '75000',
        ])->assertStatus(200);

        $this->assertSame(75000, app(SettingService::class)->minOrderAmount());

        $this->putJson('/api/super/settings', [
            'key' => 'otp_expiry_seconds',
            'value' => '30',
        ])->assertStatus(422);

        $this->patchJson('/api/super/settings/bulk', [
            'settings' => [
                ['key' => 'otp_expiry_seconds', 'value' => '180'],
                ['key' => 'otp_max_attempts', 'value' => '7'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('settings', ['key' => 'otp_expiry_seconds', 'value' => '180']);
        $this->assertDatabaseHas('settings', ['key' => 'otp_max_attempts', 'value' => '7']);

        $this->as($admin)->getJson('/api/super/settings')->assertStatus(403);
    }
}
