<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Shared\Services\SMS\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;
use Tests\TestCase;

final class SmsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';
        parent::tearDown();
    }

    public function test_generic_http_gateway_sends_configured_payload_and_token(): void
    {
        $this->app['env'] = 'local';
        config()->set('sms.driver', 'http');
        config()->set('sms.from', 'Uvita');
        config()->set('sms.http', [
            'url' => 'https://sms-gateway.test/send',
            'token' => 'secret-token',
            'token_header' => 'X-API-Key',
            'token_prefix' => '',
            'phone_field' => 'recipient',
            'message_field' => 'text',
            'from_field' => 'sender',
        ]);
        Http::fake([
            'https://sms-gateway.test/send' => Http::response(['ok' => true]),
        ]);

        app(SmsService::class)->send('+998901234567', 'Uvita test xabari');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sms-gateway.test/send'
            && $request->hasHeader('X-API-Key', 'secret-token')
            && $request['recipient'] === '+998901234567'
            && $request['text'] === 'Uvita test xabari'
            && $request['sender'] === 'Uvita');
    }

    public function test_eskiz_authenticates_and_sends_sms(): void
    {
        $this->app['env'] = 'local';
        Cache::forget('sms.eskiz.token');
        config()->set('sms.driver', 'eskiz');
        config()->set('sms.from', '4546');
        config()->set('sms.eskiz', [
            'base_url' => 'https://notify.eskiz.uz/api',
            'email' => 'test@uvita.uz',
            'password' => 'secret',
            'callback_url' => null,
        ]);
        Http::fake([
            'https://notify.eskiz.uz/api/auth/login' => Http::response([
                'data' => ['token' => 'eskiz-token'],
            ]),
            'https://notify.eskiz.uz/api/message/sms/send' => Http::response([
                'status' => 'waiting',
            ]),
        ]);

        app(SmsService::class)->send('+998901234567', 'OTP: 123456');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://notify.eskiz.uz/api/message/sms/send'
            && $request->hasHeader('Authorization', 'Bearer eskiz-token')
            && $request['mobile_phone'] === '998901234567'
            && $request['message'] === 'OTP: 123456'
            && $request['from'] === '4546');
    }

    public function test_production_rejects_log_driver(): void
    {
        $this->app['env'] = 'production';
        config()->set('sms.driver', 'log');

        $this->expectException(LogicException::class);

        app(SmsService::class)->send('+998901234567', 'Test');
    }
}
