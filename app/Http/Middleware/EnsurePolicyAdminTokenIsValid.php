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
        if (! (bool) config('security.policy_admin.enabled')) {
            abort(404);
        }

        $configuredToken = config('policy.admin_token');
        $providedToken = $request->header('X-Lootwright-Policy-Admin-Token');
        $minimumLength = max(32, (int) config('security.policy_admin.minimum_token_length', 32));

        if (! is_string($configuredToken)
            || strlen($configuredToken) < $minimumLength
            || ! is_string($providedToken)
            || strlen($providedToken) < $minimumLength
            || ! hash_equals($configuredToken, $providedToken)
            || $request->hasHeader('X-Lootwright-Privacy-Session')
        ) {
            abort(404);
        }

        return $next($request);
    }
}
