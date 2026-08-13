<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Tests\TestCase;

final class ProductionSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payment.test_mode', false);
    }

    public function test_payme_fails_closed_when_production_key_is_missing(): void
    {
        config()->set('payment.payme.key', '');

        $this->withBasicAuthHeader('Paycom', '')
            ->postJson('/api/payment/payme/webhook', [
                'method' => 'CheckPerformTransaction',
                'params' => [],
                'id' => 1,
            ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', -32504);
    }

    public function test_payme_accepts_only_the_configured_production_key(): void
    {
        config()->set('payment.payme.key', 'real-secret');

        $this->withBasicAuthHeader('Paycom', 'wrong')
            ->postJson('/api/payment/payme/webhook', [
                'method' => 'UnknownMethod',
                'params' => [],
                'id' => 1,
            ])
            ->assertUnauthorized();

        $this->withBasicAuthHeader('Paycom', 'real-secret')
            ->postJson('/api/payment/payme/webhook', [
                'method' => 'UnknownMethod',
                'params' => [],
                'id' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('error.code', -32601);
    }

    public function test_uzum_fails_closed_and_accepts_only_configured_basic_auth(): void
    {
        config()->set('payment.uzum.username', '');
        config()->set('payment.uzum.password', '');

        $this->postJson('/api/payment/uzum/webhook', ['method' => 'GetInformation'])
            ->assertUnauthorized();

        config()->set('payment.uzum.username', 'uzum-user');
        config()->set('payment.uzum.password', 'uzum-password');

        $this->withBasicAuthHeader('uzum-user', 'wrong')
            ->postJson('/api/payment/uzum/webhook', ['method' => 'GetInformation'])
            ->assertUnauthorized();

        $this->withBasicAuthHeader('uzum-user', 'uzum-password')
            ->postJson('/api/payment/uzum/webhook', [
                'method' => 'GetInformation',
                'orderId' => 999999,
            ])
            ->assertOk()
            ->assertJsonPath('status', -1);
    }

    public function test_click_rejects_missing_credentials_and_invalid_service_id(): void
    {
        config()->set('payment.click.service_id', '');
        config()->set('payment.click.secret_key', '');

        $payload = [
            'action' => 0,
            'service_id' => 'service',
            'click_trans_id' => 'click-1',
            'merchant_trans_id' => '999999',
            'amount' => '1000',
            'sign_time' => '2026-07-24 10:00:00',
            'sign_string' => 'invalid',
        ];

        $this->postJson('/api/payment/click/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('error', -9);

        config()->set('payment.click.service_id', 'configured-service');
        config()->set('payment.click.secret_key', 'click-secret');

        $this->postJson('/api/payment/click/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('error', -9);
    }

    private function withBasicAuthHeader(string $username, string $password): static
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$username}:{$password}"),
        ]);
    }
}
