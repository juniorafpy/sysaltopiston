<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Pais;
use App\Observers\PaisObserver;

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
        Pais::observe(PaisObserver::class);
    }
}
