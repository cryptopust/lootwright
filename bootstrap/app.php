<?php

use App\Http\Controllers\ReadinessController;
use App\Http\Middleware\EnsurePolicyAdminTokenIsValid;
use App\Http\Middleware\EnsureReadinessTokenIsValid;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: static function (): void {
            Route::get('/up', static fn (): Response => response(
                'OK',
                200,
                [
                    'Cache-Control' => 'no-store',
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ],
            ))->name('liveness');

            Route::get('/ready', ReadinessController::class)
                ->middleware(['readiness', 'throttle:10,1'])
                ->name('readiness');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'policy.admin' => EnsurePolicyAdminTokenIsValid::class,
            'readiness' => EnsureReadinessTokenIsValid::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
