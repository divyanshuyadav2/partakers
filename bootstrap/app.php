<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.authenticated' => \App\Http\Middleware\ApiAuthenticated::class,
            'organization.context' => \App\Http\Middleware\OrganizationContext::class,
            'organization.selected' => \App\Http\Middleware\RedirectIfOrganizationSelected::class,
            'contact.owner' => \App\Http\Middleware\VerifyContactOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
