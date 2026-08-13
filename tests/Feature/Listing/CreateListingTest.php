<?php

declare(strict_types=1);

namespace Tests\Feature\Listing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Listing\Domain\Enums\ListingStatus;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class CreateListingTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::create(['phone' => '+998901234567', 'name' => 'Ali']);

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeaders(['Authorization' => "Bearer {$token}"]);

        return $user;
    }

    private function basePayload(array $overrides = []): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'sabzavotlar'],
            ['name' => 'Sabzavotlar', 'is_active' => true],
        );

        return array_merge([
            'category_id' => $category->id,
            'title'       => 'Qizil kartoshka',
            'price'       => 5000,
            'quantity'    => 100,
            'unit'        => 'kg',
            'images'      => ['storage/listings/test.jpg'],
            'region'      => 'Toshkent',
        ], $overrides);
    }

    public function test_create_listing_stores_expires_at(): void
    {
        $this->actingUser();

        $expires = now()->addDays(30);

        $response = $this->postJson('/api/listings', $this->basePayload([
            'expires_at' => $expires->toISOString(),
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.expires_at', fn ($v) => $v !== null);

        $this->assertDatabaseHas('listings', [
            'title'      => 'Qizil kartoshka',
            'expires_at' => $expires->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_create_listing_allows_null_expires_at(): void
    {
        $this->actingUser();

        $response = $this->postJson('/api/listings', $this->basePayload());

        $response->assertStatus(201);
        $this->assertNull(ListingModel::first()->expires_at);
    }

    public function test_expires_at_must_be_future(): void
    {
        $this->actingUser();

        $response = $this->postJson('/api/listings', $this->basePayload([
            'expires_at' => now()->subDay()->toISOString(),
        ]));

        $response->assertStatus(422);
    }

    public function test_create_listing_requires_category_images_quantity_and_region(): void
    {
        $this->actingUser();

        $this->postJson('/api/listings', $this->basePayload(['category_id' => null]))->assertStatus(422);
        $this->postJson('/api/listings', $this->basePayload(['images' => []]))->assertStatus(422);
        $this->postJson('/api/listings', $this->basePayload(['region' => null]))->assertStatus(422);
        $this->postJson('/api/listings', $this->basePayload(['quantity' => 0]))->assertStatus(422);
        $this->postJson('/api/listings', $this->basePayload(['price' => 0]))->assertStatus(422);

        $this->postJson('/api/listings', $this->basePayload())->assertStatus(201);
    }

    public function test_new_listing_is_active_immediately_without_moderation(): void
    {
        $this->actingUser();

        $this->postJson('/api/listings', $this->basePayload([
            'title' => 'Bozorga darhol chiqadi',
        ]))->assertStatus(201);

        $this->assertDatabaseHas('listings', [
            'title'  => 'Bozorga darhol chiqadi',
            'status' => ListingStatus::ACTIVE->value,
        ]);

        // Va bozorda (public index) darhol ko'rinadi
        $this->getJson('/api/listings')
            ->assertStatus(200)
            ->assertJsonPath('data.0.status', ListingStatus::ACTIVE->value);
    }

    public function test_accepts_other_units_besides_kg(): void
    {
        $this->actingUser();

        $this->postJson('/api/listings', $this->basePayload([
            'title' => 'Tuxum',
            'unit'  => 'dona',
        ]))->assertStatus(201)
            ->assertJsonPath('data.unit', 'dona');

        $this->postJson('/api/listings', $this->basePayload([
            'title' => 'Sut',
            'unit'  => 'litr',
        ]))->assertStatus(201);

        $this->postJson('/api/listings', $this->basePayload([
            'title' => "Piyoz bog'lami",
            'unit'  => "bog'lam",
        ]))->assertStatus(201);

        // Noma'lum birlik rad etiladi
        $this->postJson('/api/listings', $this->basePayload([
            'title' => 'Notog\'ri',
            'unit'  => 'metr',
        ]))->assertStatus(422);
    }

    public function test_expired_listing_hidden_from_market(): void
    {
        $user = $this->actingUser();

        ListingModel::create([
            'seller_id'  => $user->id,
            'title'      => 'Eski e\'lon',
            'slug'       => 'eski-1',
            'price'      => 1000,
            'quantity'   => 1,
            'unit'       => 'kg',
            'status'     => ListingStatus::ACTIVE->value,
            'expires_at' => now()->subDay(),
        ]);

        ListingModel::create([
            'seller_id'  => $user->id,
            'title'      => 'Yangi e\'lon',
            'slug'       => 'yangi-1',
            'price'      => 1000,
            'quantity'   => 1,
            'unit'       => 'kg',
            'status'     => ListingStatus::ACTIVE->value,
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/listings');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertSame('Yangi e\'lon', $response->json('data.0.title'));
    }
}
