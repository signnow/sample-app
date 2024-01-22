<?php

namespace App\Providers;

use Illuminate\Routing\UrlGenerator;
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
    public function boot(UrlGenerator $urlGenerator): void
    {
        if (str_starts_with(env('APP_URL'), 'https')) {
            $urlGenerator->forceScheme('https');
        }
    }
}
