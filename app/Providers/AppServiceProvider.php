<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use App\Http\Middleware\EnsureDepartment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register route middleware alias for department checks
        if ($this->app->resolved('router')) {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->aliasMiddleware('department', EnsureDepartment::class);
        }
    }
}
