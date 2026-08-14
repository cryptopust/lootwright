<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class EffectivePeriod implements JsonSerializable
{
    private function __construct(
        public RetrievedAt $startsAt,
        public ?RetrievedAt $endsAt,
    ) {}

    public static function create(RetrievedAt $startsAt, ?RetrievedAt $endsAt): DomainResult
    {
        if ($endsAt !== null && $endsAt->dateTime() <= $startsAt->dateTime()) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'An evidence effective period must end after it starts.',
            ));
        }

        return DomainResult::success(new self($startsAt, $endsAt));
    }

    public function contains(RetrievedAt $instant): bool
    {
        return $instant->dateTime() >= $this->startsAt->dateTime()
            && ($this->endsAt === null || $instant->dateTime() < $this->endsAt->dateTime());
    }

    /** @return array{starts_at: RetrievedAt, ends_at: ?RetrievedAt} */
    public function jsonSerialize(): array
    {
        return ['starts_at' => $this->startsAt, 'ends_at' => $this->endsAt];
    }
}
