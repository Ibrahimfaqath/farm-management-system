<?php

use App\Http\Middleware\ApplyLocale;
use App\Support\AbilityGuard;
use App\Support\ModuleGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module' => ModuleGuard::class,
            'ability' => AbilityGuard::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        // Must run inside the web group so the session is available when the
        // visitor's chosen language is read.
        $middleware->web(append: [ApplyLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
