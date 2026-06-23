<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Services\Settings\SettingService;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Domain\Repositories\SettingRepositoryInterface;
use Modules\Admin\Infrastructure\Persistence\Repositories\EloquentSettingRepository;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentOtpAttemptRepository;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Repositories\EloquentCartRepository;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Repositories\EloquentPaymentRepository;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Repositories\EloquentReviewRepository;
use Modules\Category\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Repositories\EloquentProductRepository;
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
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, EloquentSettingRepository::class);
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        foreach (glob(base_path('Modules/*/Infrastructure/Persistence/Migrations')) as $path) {
            $this->loadMigrationsFrom($path);
        }
    }
}
