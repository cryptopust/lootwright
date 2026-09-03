<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class FollowUpQuestionRequest
{
    /** @param list<string> $actionReferences
     * @param  array<string,mixed>  $deterministicContext
     */
    public function __construct(
        public string $question,
        public GameEdition $edition,
        public string $rulesetVersion,
        public array $actionReferences,
        public array $deterministicContext,
        public AiRequestContext $context,
    ) {
        $this->assertText($question, 500);
        $this->assertText($rulesetVersion, 128);
        if (count($actionReferences) > 50 || count($deterministicContext) > 20) {
            throw new InvalidArgumentException('AI follow-up context is too large.');
        }
        foreach ($actionReferences as $reference) {
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $reference) !== 1) {
                throw new InvalidArgumentException('Follow-up references must be canonical identifiers.');
            }
        }
    }

    private function assertText(string $value, int $max): void
    {
        if (trim($value) === '' || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) === 1) {
            throw new InvalidArgumentException('AI follow-up text is bounded plain text.');
        }
    }
}
