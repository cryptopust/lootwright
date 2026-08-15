<?php

namespace Lootwright\Application\Identity\UseCases;

use Lootwright\Application\Identity\DTO\PrivacySessionCredential;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;
use Lootwright\Application\Identity\Ports\SecretGenerator;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;

final readonly class CreateAnonymousPrivacySession
{
    public function __construct(
        private PrivacySessionRepository $sessions,
        private IdentifierGenerator $identifiers,
        private SecretGenerator $secrets,
    ) {}

    public function handle(int $ttlHours = 24): PrivacySessionCredential
    {
        return $this->sessions->create(
            $this->identifiers->uuid7(),
            $this->secrets->hex(32),
            max(1, min($ttlHours, 168)),
        );
    }
}
