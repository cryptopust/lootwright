<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireEmergencyCapability
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if (! (bool) config('security.emergency.'.$capability, false)) {
            return new JsonResponse([
                'status' => 'temporarily_disabled',
                'capability' => $capability,
            ], 503, ['Cache-Control' => 'no-store, private']);
        }

        return $next($request);
    }
}
