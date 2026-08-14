<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class AttributionRequirement implements JsonSerializable
{
    private function __construct(
        public bool $required,
        public ?string $notice,
    ) {}

    public static function none(): self
    {
        return new self(false, null);
    }

    public static function required(string $notice): DomainResult
    {
        $notice = trim($notice);

        if ($notice === '' || mb_strlen($notice) > 1000) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Required attribution must contain bounded notice text.',
            ));
        }

        return DomainResult::success(new self(true, $notice));
    }

    /** @return array{required: bool, notice: ?string} */
    public function jsonSerialize(): array
    {
        return ['required' => $this->required, 'notice' => $this->notice];
    }
}
