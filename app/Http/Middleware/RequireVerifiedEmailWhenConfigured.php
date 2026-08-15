<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireVerifiedEmailWhenConfigured
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ((bool) config('security.require_verified_email')
            && $user instanceof MustVerifyEmail
            && ! $user->hasVerifiedEmail()
        ) {
            return new JsonResponse(['status' => 'email_verification_required'], 403, [
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return $next($request);
    }
}
