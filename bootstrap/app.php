<?php

use App\Http\Controllers\ReadinessController;
use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureAdminTwoFactorEnabled;
use App\Http\Middleware\EnsurePolicyAdminTokenIsValid;
use App\Http\Middleware\EnsureReadinessTokenIsValid;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventAdminTwoFactorDisable;
use App\Http\Middleware\PerformanceTelemetry;
use App\Http\Middleware\RequireEmergencyCapability;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\RequireVerifiedEmailWhenConfigured;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->prepend(AssignCorrelationId::class);
        $middleware->append(PerformanceTelemetry::class);
        $middleware->trustHosts(
            static function (): array {
                $hosts = config('deployment.trusted_hosts', []);

                return is_array($hosts) ? $hosts : [];
            },
            subdomains: false,
        );
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'emergency' => RequireEmergencyCapability::class,
            'policy.admin' => EnsurePolicyAdminTokenIsValid::class,
            'readiness' => EnsureReadinessTokenIsValid::class,
            'verified.optional' => RequireVerifiedEmailWhenConfigured::class,
            'active' => EnsureActiveUser::class,
            'role' => RequireRole::class,
            'admin.2fa' => EnsureAdminTwoFactorEnabled::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            EnsureActiveUser::class,
            PreventAdminTwoFactorDisable::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
