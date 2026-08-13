<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_endpoints_reject_anonymous_and_wrong_roles(): void
    {
        $this->getJson('/api/user/profile')->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/dashboard/orders')->assertUnauthorized();
        $this->getJson('/api/super/transactions')->assertUnauthorized();
    }

    public function test_unknown_and_invalid_requests_return_safe_understandable_json(): void
    {
        config()->set('app.debug', false);

        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Endpoint topilmadi.']);

        $this->postJson('/api/auth/otp/send', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['phone']])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('file');
    }

    public function test_cors_allows_configured_frontend(): void
    {
        config()->set('cors.allowed_origins', ['https://uvita.example']);

        $this->withHeaders([
            'Origin' => 'https://uvita.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/products')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://uvita.example');
    }

    public function test_cors_rejects_unknown_origin(): void
    {
        config()->set('cors.allowed_origins', ['https://uvita.example']);

        $unknownOriginResponse = $this->withHeaders([
            'Origin' => 'https://evil.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/products')
            ->assertNoContent();

        $this->assertNotSame(
            'https://evil.example',
            $unknownOriginResponse->headers->get('Access-Control-Allow-Origin')
        );
    }

    public function test_general_api_rate_limit_is_enforced(): void
    {
        config()->set('security.api_rate_limit_per_minute', 2);
        RateLimiter::clear('api:10.20.30.40');
        $client = $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40']);

        $client->getJson('/api/products')->assertOk();
        $client->getJson('/api/products')->assertOk();
        $client->getJson('/api/products')->assertTooManyRequests();
    }
}
