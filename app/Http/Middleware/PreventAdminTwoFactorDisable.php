<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PreventAdminTwoFactorDisable
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($request->isMethod('DELETE')
            && $request->is('user/two-factor-authentication')
            && $user instanceof User
            && $user->isAdmin()
        ) {
            abort(403, 'Admin hesaplarında iki aşamalı doğrulama zorunludur.');
        }

        return $next($request);
    }
}
