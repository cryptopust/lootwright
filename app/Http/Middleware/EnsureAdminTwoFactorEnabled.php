<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminTwoFactorEnabled
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && $user->isAdmin() && $user->two_factor_confirmed_at === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Admin hesapları için iki aşamalı doğrulama zorunludur.'], 403)
                : redirect()->route('profile.security')->with('warning', 'Admin işlemleri için iki aşamalı doğrulamayı etkinleştirin.');
        }

        return $next($request);
    }
}
