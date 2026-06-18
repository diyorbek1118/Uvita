<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes();
    }

    private function registerModuleRoutes(): void
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