<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Repositories\OtpAttemptRepositoryInterface;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentOtpAttemptRepository;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
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
    }

    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadLegacyModuleRoutes();
    }

    private function loadModuleMigrations(): void
    {
        foreach (glob(base_path('Modules/*/Infrastructure/Persistence/Migrations')) as $path) {
            $this->loadMigrationsFrom($path);
        }
    }

    // O'tish davri: DDD ga ko'chirilmagan eski modullar uchun
    private function loadLegacyModuleRoutes(): void
    {
        $modules = ['Auth', 'User', 'Product', 'Cart', 'Order'];

        foreach ($modules as $module) {
            $routeFile = base_path("Modules/{$module}/routes/api.php");

            if (file_exists($routeFile)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($routeFile);
            }
        }
    }
}
