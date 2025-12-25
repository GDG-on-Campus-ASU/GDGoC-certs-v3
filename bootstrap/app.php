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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias([
            'superadmin' => \App\Http\Middleware\EnsureUserIsSuperadmin::class,
            'admin_or_superadmin' => \App\Http\Middleware\EnsureUserIsAdminOrSuperadmin::class,
            'org_name' => \App\Http\Middleware\EnsureOrgNameIsSet::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
