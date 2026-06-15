<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Force HTTPS scheme in production so signed URLs match the actual domain.
        // Without this, signed URL verification fails with 403 on cPanel/proxy setups.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            
            if (!$this->app->runningInConsole()) {
                $_SERVER['HTTPS'] = 'on';
                request()->server->set('HTTPS', 'on');
            }
        }
    }
}
