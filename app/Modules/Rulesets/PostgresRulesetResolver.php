<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Lootwright\Domain\Rulesets\Ports\ActiveRulesetResolver;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\RulesetCompatibilityChecker;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Rulesets\RulesetResolution;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Throwable;

final readonly class PostgresRulesetResolver implements ActiveRulesetResolver, RulesetResolver
{
    public function __construct(
        private PostgresRulesetRepository $rulesets,
        private RulesetCompatibilityChecker $compatibility,
    ) {}

    public function resolve(
        GameEdition $edition,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
    ): DomainResult {
        if (! $patch->belongsTo($edition)
            || ($league !== null && ! $league->belongsTo($edition))
            || ! $parserVersion->belongsTo($edition)
        ) {
            return $this->failure(DomainErrorCode::EditionMismatch, 'Ruleset resolution scope must belong to one game edition.');
        }

        try {
            $resolution = $this->resolveActive($edition, $patch, $league, $parserVersion);
            if ($resolution->compatible()) {
                return DomainResult::success($resolution->ruleset->identity);
            }

            return $this->failure(DomainErrorCode::RulesetMismatch, 'Ruleset resolution failed with status: '.$resolution->status->value.'.');
        } catch (Throwable) {
            return $this->failure(DomainErrorCode::RulesetMismatch, 'The ruleset catalog is unavailable; resolution failed closed.');
        }
    }

    public function resolveActive(GameEdition $edition, PatchVersion $patch, ?LeagueId $league, ParserVersion $parserVersion): RulesetResolution
    {
        $requestedLeague = $league?->value;
        if (! $patch->belongsTo($edition)
            || ($league !== null && ! $league->belongsTo($edition))
            || ! $parserVersion->belongsTo($edition)
        ) {
            return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, RulesetCompatibilityStatus::Unavailable);
        }

        $base = DB::table('ruleset_activations')->where('game_edition', $edition->value);
        if (! $base->exists()) {
            return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, RulesetCompatibilityStatus::Unavailable);
        }
        $patchScope = (clone $base)->where('patch', $patch->value)->where('league_key', $requestedLeague ?? '');
        if (! $patchScope->exists()) {
            return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, RulesetCompatibilityStatus::UnsupportedPatch);
        }
        $activeId = $patchScope->where('parser_version', $parserVersion->value)->value('ruleset_version_id');
        if (! is_string($activeId)) {
            return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, RulesetCompatibilityStatus::IncompatibleParser);
        }
        $ruleset = $this->rulesets->findById($activeId);
        if ($ruleset === null) {
            return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, RulesetCompatibilityStatus::Unavailable);
        }
        $status = $this->compatibility->check($edition, $patch->value, $requestedLeague, $parserVersion->value, $ruleset);

        return new RulesetResolution($edition, $patch->value, $requestedLeague, $parserVersion->value, $status, $ruleset);
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}
