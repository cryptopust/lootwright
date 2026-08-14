<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class DataSource implements JsonSerializable
{
    private function __construct(
        public string $id,
        public string $name,
        public SourceType $type,
        public AccessMode $accessMode,
    ) {}

    public static function create(
        string $id,
        string $name,
        SourceType $type,
        AccessMode $accessMode,
    ): DomainResult {
        $id = trim($id);
        $name = trim($name);

        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $id) !== 1
            || $name === ''
            || mb_strlen($name) > 160
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'Data sources require a canonical ID and bounded display name.',
            ));
        }

        return DomainResult::success(new self($id, $name, $type, $accessMode));
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source_type' => $this->type->value,
            'access_mode' => $this->accessMode->value,
        ];
    }
}
