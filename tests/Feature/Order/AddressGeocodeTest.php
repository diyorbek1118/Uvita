<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\Feature\Concerns\SeedsSettings;
use Tests\TestCase;

/**
 * Buyurtma yaratishda manzilni geokodlash:
 *  - lat/lng orders jadvaliga saqlanadi
 *  - natija address_geocodes jadvalida keshlanadi (qayta so'rov ketmaydi)
 */
class AddressGeocodeTest extends TestCase
{
    use RefreshDatabase, SeedsSettings;

    private User     $customer;
    private Category $category;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedSettings();

        $this->customer = User::create(['phone' => '+998901234567', 'name' => 'Ali']);
        $this->category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product  = Product::create([
            'name'        => 'Mahsulot',
            'slug'        => 'mahsulot',
            'description' => 'Tavsif',
            'price'       => 30000,
            'stock'       => 10,
            'status'      => 'active',
            'images'      => [],
            'category_id' => $this->category->id,
        ]);
    }

    private function asCustomer(): static
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    private function validOrderPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'items'          => [['product_id' => $this->product->id, 'quantity' => 2]],
            'address'        => [
                'region'   => 'Toshkent',
                'district' => 'Yunusobod',
                'street'   => 'Navoiy',
                'house'    => '1',
            ],
            'phone'          => '+998901234567',
            'delivery_time'  => 'Ertaga 14:00-18:00',
            'payment_method' => 'payme',
        ], $overrides);
    }

    private function fakeNominatim(array $hit = ['lat' => 41.311081, 'lon' => 69.279737]): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response($hit ? [$hit] : []),
        ]);
    }

    public function test_order_creation_geocodes_and_stores_coordinates(): void
    {
        $this->fakeNominatim();

        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.lat', 41.311081)
            ->assertJsonPath('data.lng', 69.279737)
            ->assertJsonPath('data.geo_level', 'address');

        $order = OrderModel::first();
        $this->assertNotNull($order->lat);
        $this->assertNotNull($order->lng);
        $this->assertSame('address', $order->geo_level);

        // Natija keshlangan
        $this->assertDatabaseHas('address_geocodes', [
            'query' => 'Toshkent, Yunusobod, Navoiy, 1, Uzbekistan',
        ]);
    }

    public function test_region_fallback_marks_geo_level_region(): void
    {
        // To'liq manzil topilmaydi, hudud topiladi
        $this->fakeNominatimWithCalls([
            'nominatim.openstreetmap.org/search?*Navoiy*' => Http::response([]),
            'nominatim.openstreetmap.org/search?*Toshkent*' => Http::response([
                ['lat' => 41.3123363, 'lon' => 69.2787079],
            ]),
        ]);

        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.lat', 41.3123363)
            ->assertJsonPath('data.lng', 69.2787079)
            ->assertJsonPath('data.geo_level', 'region');

        $order = OrderModel::first();
        $this->assertSame('region', $order->geo_level);
    }

    public function test_not_found_address_is_cached_as_miss(): void
    {
        // Xizmat normal javob, lekin natija yo'q → miss keshlanadi
        $this->fakeNominatim([]);

        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.lat', null)
            ->assertJsonPath('data.lng', null);

        $this->assertDatabaseHas('address_geocodes', [
            'query' => 'Toshkent, Yunusobod, Navoiy, 1, Uzbekistan',
            'lat'   => null,
            'lng'   => null,
        ]);
    }

    public function test_second_order_with_same_address_uses_cache(): void
    {
        $this->fakeNominatim();

        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());
        $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        // Nominatim'ga faqat 1 marta so'rov — ikkinchi buyurtma keshdan
        Http::assertSentCount(1);

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('address_geocodes', 1);
    }

    public function test_client_provided_coordinates_are_used_without_geocoding(): void
    {
        Http::fake();

        $payload = $this->validOrderPayload([
            'address' => [
                'region'   => 'Toshkent',
                'district' => 'Yunusobod',
                'street'   => 'Navoiy',
                'house'    => '2',
                'lat'      => 41.312,
                'lng'      => 69.281,
            ],
        ]);

        $response = $this->asCustomer()->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.lat', 41.312)
            ->assertJsonPath('data.lng', 69.281)
            ->assertJsonPath('data.geo_level', 'address');

        Http::assertNothingSent();

        // Mijoz koordinatalari ham keshlangan (keyingi buyurtmalar uchun)
        $this->assertDatabaseHas('address_geocodes', [
            'query' => 'Toshkent, Yunusobod, Navoiy, 2, Uzbekistan',
        ]);
    }

    public function test_geocoding_failure_does_not_block_order_creation(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response('Internal', 500),
        ]);

        $response = $this->asCustomer()->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.lat', null)
            ->assertJsonPath('data.lng', null)
            ->assertJsonPath('data.geo_level', null);

        $this->assertDatabaseCount('orders', 1);
        // Xato (5xx) keshlanmaydi — keyingi buyurtmada qayta uriniladi
        $this->assertDatabaseCount('address_geocodes', 0);
    }

    /** Http::fake() ni callable bilan o'rnatish — URL bo'yicha farqlash uchun */
    private function fakeNominatimWithCalls(array $patterns): void
    {
        Http::fake($patterns);
    }
}
