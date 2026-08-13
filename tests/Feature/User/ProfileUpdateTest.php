<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/user/profile';

    private function actingUser(): User
    {
        $user = User::create([
            'phone'    => '+998901234567',
            'name'     => 'Ali',
            'surname'  => 'Valiyev',
            'region'   => 'Toshkent',
            'address'  => 'Chilonzor 12',
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeaders(['Authorization' => "Bearer {$token}"]);

        return $user;
    }

    public function test_partial_update_changes_only_provided_fields(): void
    {
        $user = $this->actingUser();

        $response = $this->putJson($this->endpoint, [
            'region' => 'Samarqand',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertSame('Samarqand', $user->region);
        $this->assertSame('Ali', $user->name);          // o'zgarmadi
        $this->assertSame('Chilonzor 12', $user->address); // o'zgarmadi
    }

    public function test_update_empty_optional_field_sets_null(): void
    {
        $user = $this->actingUser();

        $response = $this->putJson($this->endpoint, [
            'address' => '',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->address);
    }

    public function test_update_lat_lng(): void
    {
        $user = $this->actingUser();

        $response = $this->putJson($this->endpoint, [
            'lat' => 41.3111587,
            'lng' => 69.2797371,
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertSame(41.3111587, (float) $user->lat);
        $this->assertSame(69.2797371, (float) $user->lng);
        $this->assertSame('Ali', $user->name); // boshqa maydon o'zgarmadi
    }

    public function test_update_without_name_allowed(): void
    {
        $this->actingUser();

        $response = $this->putJson($this->endpoint, [
            'surname' => 'Karimov',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_requires_auth(): void
    {
        $response = $this->putJson($this->endpoint, ['name' => 'Ali']);

        $response->assertStatus(401);
    }
}
