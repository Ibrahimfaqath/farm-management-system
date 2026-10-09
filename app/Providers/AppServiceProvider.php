<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // Laravel ships a Tailwind pagination view that hardcodes grey and blue
        // utilities, which fights the FARM theme. Point every paginator at the
        // DaisyUI view instead.
        Paginator::defaultView('pagination::farm');
        Paginator::defaultSimpleView('pagination::farm');
    }
}
