<?php

namespace App\Modules\Analysis\Infrastructure;

use App\Modules\BuildIntake\PobImportRejected;
use App\Modules\BuildIntake\PobPolicyDenied;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\ArtifactParser;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class PolicyGatedArtifactParser implements ArtifactParser
{
    public function __construct(private PolicyGatedPobImporter $pobImporter) {}

    public function parse(string $artifactType, string $contents, GameEdition $expectedEdition): ParsedArtifact
    {
        if ($artifactType !== 'pob') {
            throw new TerminalWorkflowFailure('unsupported_artifact_type', 'Only explicitly submitted PoB text is supported.');
        }

        try {
            $result = $this->pobImporter->handle($contents, false)->result;
        } catch (PobPolicyDenied $exception) {
            throw new PolicyBlocked($exception->decision);
        } catch (PobImportRejected $exception) {
            throw new TerminalWorkflowFailure($exception->domainError->code->value, 'The submitted build artifact is invalid.');
        }

        if ($result->canonicalBuild->edition !== $expectedEdition) {
            throw new TerminalWorkflowFailure('edition_mismatch', 'The detected build edition differs from the requested edition.');
        }

        $normalized = CanonicalJson::encode($result);
        $buildVersion = $result->canonicalBuild->buildVersion;
        $patch = is_string($buildVersion) && preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:[a-z])?$/D', $buildVersion) === 1
            ? $buildVersion
            : null;
        $clarifications = [];

        if ($patch === null) {
            $clarifications[] = [
                'code' => 'exact_patch_required',
                'question' => 'Which exact game patch should this build use?',
            ];
        }

        if ($expectedEdition === GameEdition::Poe2) {
            $clarifications[] = [
                'code' => 'poe2_analysis_inactive',
                'question' => 'PoE2 analysis is not active; submit a PoE1 build for the current MVP.',
            ];
        }

        return new ParsedArtifact(
            $expectedEdition,
            $expectedEdition === GameEdition::Poe1 ? 'pob1' : 'pob2-beta',
            $result->parserVersion,
            $normalized,
            hash('sha256', $normalized),
            $patch,
            null,
            $clarifications,
        );
    }
}
