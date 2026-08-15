<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Tests\TestCase;

final class MultiShopSellerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_phone_seller_and_seller_selects_one_of_multiple_shops(): void
    {
        $admin = Staff::create([
            'name' => 'Admin',
            'email' => 'admin-multi-shop@uvita.uz',
            'password' => 'password123',
            'role' => StaffRole::ADMIN,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Ali Seller',
            'phone' => '+998901112233',
            'password' => 'password123',
            'business_name' => 'Baraka Market',
            'region' => 'Toshkent',
            'district' => 'Yunusobod',
            'address' => 'Amir Temur 1',
        ];

        $created = $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/admin/sellers/accounts', $payload)
            ->assertCreated()
            ->assertJsonPath('data.phone', '+998901112233')
            ->assertJsonCount(1, 'data.shops');

        $sellerId = (int) $created->json('data.id');
        $this->postJson("/api/admin/sellers/{$sellerId}/shops", [
            'business_name' => 'Baraka Chilonzor',
            'region' => 'Toshkent',
            'district' => 'Chilonzor',
            'address' => 'Bunyodkor 10',
        ])->assertCreated();

        $login = $this->postJson('/api/seller/login', [
            'phone' => '+998901112233',
            'password' => 'password123',
        ])->assertOk()->assertJsonCount(2, 'data.shops');

        $token = (string) $login->json('data.token');
        $secondShopId = (int) $login->json('data.shops.1.id');
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->withHeader('X-Seller-Shop-Id', (string) $secondShopId)
            ->getJson('/api/seller/profile')
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Baraka Chilonzor');
    }

    public function test_seller_cannot_select_another_sellers_shop(): void
    {
        [$first, $firstShop] = $this->sellerWithShop('+998901112234', 'Birinchi');
        [, $otherShop] = $this->sellerWithShop('+998901112235', 'Boshqa');

        $this->withToken($first->createToken('seller')->plainTextToken)
            ->withHeader('X-Seller-Shop-Id', (string) $otherShop)
            ->getJson('/api/seller/profile')
            ->assertNotFound();

        $this->assertNotSame($firstShop, $otherShop);
    }

    private function sellerWithShop(string $phone, string $businessName): array
    {
        $seller = Staff::create([
            'name' => $businessName,
            'email' => strtolower($businessName).'@uvita.local',
            'phone' => $phone,
            'password' => 'password123',
            'role' => StaffRole::SELLER,
            'is_active' => true,
        ]);
        $shop = $seller->sellerProfiles()->create([
            'business_name' => $businessName,
            'legal_type' => 'individual',
            'tin' => substr(preg_replace('/\D/', '', $phone), -9),
            'phone' => $phone,
            'region' => 'Toshkent',
            'district' => 'Yunusobod',
            'address' => 'Test',
            'bank_account' => '20208000900000000001',
            'bank_mfo' => '00001',
            'terms_accepted' => true,
            'is_active' => true,
        ]);

        return [$seller, $shop->id];
    }
}
