<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'applicant' => \App\Http\Middleware\ApplicantOnly::class,
        ]);

        // Trust all proxies (cPanel shared hosting / CloudFlare).
        // This ensures the request scheme is detected correctly for signed URL validation.
        $middleware->trustProxies(at: '*');

        // The public email-start endpoint is already throttled and only accepts
        // an email address. Excluding it avoids cPanel/browser session-cookie
        // mismatches causing Page Expired (419) before users can request a link.
        $middleware->validateCsrfTokens(except: [
            'register',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        \SocialiteProviders\Manager\ServiceProvider::class,
    ])
    ->create();
