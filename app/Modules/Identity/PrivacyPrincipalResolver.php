<?php

namespace App\Modules\Identity;

use Illuminate\Http\Request;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;

final readonly class PrivacyPrincipalResolver
{
    public function __construct(private PrivacySessionRepository $sessions) {}

    public function resolve(Request $request): ?string
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (is_int($identifier) || is_string($identifier)) {
            return (string) $identifier;
        }

        $credential = $request->header('X-Lootwright-Privacy-Session');

        return is_string($credential) ? $this->sessions->resolve($credential) : null;
    }
}
