<?php

namespace Lootwright\Domain\Shared\Evidence;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class TraceStep implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $evidenceKeys
     */
    private function __construct(
        public string $code,
        public string $statement,
        public array $evidenceKeys,
        public ?RuleReference $rule,
    ) {}

    /**
     * @param  array<array-key, mixed>  $evidenceKeys
     */
    public static function create(
        string $code,
        string $statement,
        array $evidenceKeys,
        ?RuleReference $rule = null,
    ): DomainResult {
        $code = trim($code);
        $statement = trim($statement);

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1
            || $statement === ''
            || mb_strlen($statement) > 500
            || $evidenceKeys === []
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'An explanation step requires a canonical code, statement, and evidence.',
            ));
        }

        $validatedEvidence = [];

        foreach ($evidenceKeys as $evidenceKey) {
            if (! is_string($evidenceKey)
                || preg_match('/^[a-z][a-z0-9._:-]{1,127}$/D', $evidenceKey) !== 1
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidIdentifier,
                    'Explanation evidence keys must be canonical.',
                ));
            }

            $validatedEvidence[] = $evidenceKey;
        }

        if (count($validatedEvidence) !== count(array_unique($validatedEvidence))) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::DuplicateValue,
                'Explanation evidence keys cannot be duplicated.',
            ));
        }

        return DomainResult::success(new self(
            $code,
            $statement,
            $validatedEvidence,
            $rule,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'statement' => $this->statement,
            'evidence_keys' => $this->evidenceKeys,
            'rule' => $this->rule,
        ];
    }
}
