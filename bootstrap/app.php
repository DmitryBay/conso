<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureCompanyAccess;
use App\Http\Middleware\EnsureGuestSession;
use App\Http\Middleware\SetGuestLocale;
use App\Http\Middleware\SetWorkspaceLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'company' => EnsureCompanyAccess::class,
            'guest.hotel' => EnsureGuestSession::class,
            'guest.locale' => SetGuestLocale::class,
            'workspace.locale' => SetWorkspaceLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
