<?php

namespace App\Providers;

use App\Contracts\RestaurantProviderInterface;
use App\Services\GooglePlacesAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RestaurantProviderInterface::class, GooglePlacesAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
