<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Services\Settings\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Domain\Repositories\SettingRepositoryInterface;
use Modules\Admin\Infrastructure\Persistence\Repositories\EloquentSettingRepository;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentOtpAttemptRepository;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Repositories\EloquentCartRepository;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Infrastructure\Services\EloquentCourierNotifier;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;
use Modules\Listing\Infrastructure\Persistence\Repositories\EloquentListingRepository;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Repositories\EloquentPaymentRepository;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Repositories\EloquentReviewRepository;
use Modules\Seller\Domain\Repositories\SellerOrderReadRepositoryInterface;
use Modules\Seller\Infrastructure\Persistence\Repositories\EloquentSellerOrderReadRepository;
use Modules\User\Domain\Repositories\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(OtpAttemptRepositoryInterface::class, EloquentOtpAttemptRepository::class);
        $this->app->bind(TokenServiceInterface::class, SanctumTokenService::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(CourierNotifierInterface::class, EloquentCourierNotifier::class);
        $this->app->bind(ListingRepositoryInterface::class, EloquentListingRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, EloquentSettingRepository::class);
        $this->app->bind(SellerOrderReadRepositoryInterface::class, EloquentSellerOrderReadRepository::class);
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('security.api_rate_limit_per_minute', 300))
                ->by('api:'.$identity);
        });

        RateLimiter::for('otp-send', function (Request $request): array {
            $phone = (string) $request->input('phone', 'unknown');

            return [
                Limit::perMinute(5)->by('otp-send-ip:'.$request->ip()),
                Limit::perHour(10)->by('otp-send-phone:'.$phone),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request): array {
            $phone = (string) $request->input('phone', 'unknown');

            return [
                Limit::perMinute(10)->by('otp-verify:'.$request->ip().'|'.$phone),
            ];
        });

        RateLimiter::for('staff-login', function (Request $request): array {
            $identity = mb_strtolower((string) ($request->input('email') ?: $request->input('phone', 'unknown')));

            return [
                Limit::perMinute(5)->by('staff-login:'.$request->ip().'|'.$identity),
                Limit::perMinute(30)->by('staff-login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('courier-actions', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(90)->by('courier-actions:'.$identity);
        });

        RateLimiter::for('courier-location', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)->by('courier-location:'.$identity);
        });

        foreach (glob(base_path('Modules/*/Infrastructure/Persistence/Migrations')) as $path) {
            $this->loadMigrationsFrom($path);
        }
    }
}
