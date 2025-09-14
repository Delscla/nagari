<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
 ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [

        ]);

        $middleware->alias([
                'auth' => \App\Http\Middleware\Authenticate::class, // 👈 tambahkan ini
                'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
                'tenant' => \App\Http\Middleware\TenantMiddleware::class,
                'role' => \App\Http\Middleware\RoleMiddleware::class,
                'subrole' => \App\Http\Middleware\SubRoleMiddleware::class,
        ]);

    $middleware->alias([
        'tenant' => \App\Http\Middleware\TenantMiddleware::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'subrole' => \App\Http\Middleware\SubRoleMiddleware::class,
    ]);
})

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
