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
        \Illuminate\Support\Facades\Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        // Force HTTPS scheme in production so signed URLs match the actual domain.
        // Without this, signed URL verification fails with 403 on cPanel/proxy setups.
        $isProduction = $this->app->environment('production') 
            || str_contains(config('app.url'), 'amis.edu.ph')
            || (!app()->runningInConsole() && str_contains(request()->getHost(), 'amis.edu.ph'));

        if ($isProduction) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
            
            if (!$this->app->runningInConsole()) {
                $_SERVER['HTTPS'] = 'on';
                request()->server->set('HTTPS', 'on');
            }
        }
    }
}
