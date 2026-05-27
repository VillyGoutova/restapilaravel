<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        $this->configureApiRateLimiting();

        Route::bind('product', function (string $value): Product {
            return Product::query()
                ->whereKey($value)
                ->where('is_active', true)
                ->firstOrFail();
        });
    }

    protected function configureApiRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('api.rate_limit.per_minute'))
                ->by($request->ip());
        });

        RateLimiter::for('api-products', function (Request $request) {
            return Limit::perMinute(config('api.rate_limit.products_per_minute'))
                ->by($request->ip());
        });

        RateLimiter::for('api-search', function (Request $request) {
            return Limit::perMinute(config('api.rate_limit.search_per_minute'))
                ->by($request->ip());
        });
    }
}
