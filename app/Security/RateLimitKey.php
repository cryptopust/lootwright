<?php

namespace App\Security;

use Illuminate\Http\Request;

final class RateLimitKey
{
    public static function for(Request $request, string $scope): string
    {
        $principal = $request->user()?->getAuthIdentifier();
        $privacy = $request->header('X-Lootwright-Privacy-Session');
        $identity = is_int($principal) || is_string($principal)
            ? 'account:'.$principal
            : (is_string($privacy) && $privacy !== '' ? 'privacy:'.$privacy : 'ip:'.($request->ip() ?? 'unknown'));

        return hash_hmac('sha256', $scope."\0".$identity, hash('sha256', (string) config('app.key')));
    }
}
