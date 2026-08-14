<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePolicyAdminTokenIsValid
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('policy.admin_token');
        $providedToken = $request->header('X-Lootwright-Policy-Admin-Token');

        if (! is_string($configuredToken)
            || $configuredToken === ''
            || ! is_string($providedToken)
            || ! hash_equals($configuredToken, $providedToken)
        ) {
            abort(404);
        }

        return $next($request);
    }
}
