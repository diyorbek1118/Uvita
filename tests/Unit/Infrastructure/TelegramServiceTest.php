<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Jobs\SendTelegramJob;
use App\Shared\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class TelegramServiceTest extends TestCase
{
    public function test_role_message_is_sent_to_every_configured_chat(): void
    {
        config()->set('telegram.bot_token', 'bot-token');
        config()->set('telegram.chat_ids.manager', ['101', '202']);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $sent = app(TelegramService::class)->sendToManager('<b>Test</b>');

        $this->assertTrue($sent);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request['text'] === '<b>Test</b>'
            && $request['parse_mode'] === 'HTML');
    }

    public function test_missing_role_chats_returns_false_without_http_request(): void
    {
        config()->set('telegram.bot_token', 'bot-token');
        config()->set('telegram.chat_ids.admin', []);
        Http::fake();

        $this->assertFalse(app(TelegramService::class)->sendToAdmin('Test'));
        Http::assertNothingSent();
    }

    public function test_required_telegram_job_fails_when_message_is_not_sent(): void
    {
        config()->set('telegram.required', true);
        config()->set('telegram.bot_token', 'bot-token');
        config()->set('telegram.chat_ids.courier', []);

        $this->expectException(RuntimeException::class);

        (new SendTelegramJob('courier', 'Test'))->handle(app(TelegramService::class));
    }
}
