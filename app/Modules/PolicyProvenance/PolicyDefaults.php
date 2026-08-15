<?php

namespace App\Modules\PolicyProvenance;

use Lootwright\Domain\PolicyProvenance\AccessMode;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\SourceType;

final class PolicyDefaults
{
    public const POLICY_VERSION = '1.0.0';

    public const REVIEWED_AT = '2026-08-14T13:16:00Z';

    public const REVIEW_EXPIRES_AT = '2026-11-12T00:00:00Z';

    private function __construct() {}

    /** @return list<array<string, string>> */
    public static function sources(): array
    {
        return [
            self::source('LOOTWRIGHT-MANUAL-TRADE', 'Lootwright manual Trade recipe schema', SourceType::FirstPartyOriginal, AccessMode::LocalRuntime, 'Original local-only recipe generation; no Trade endpoint, listing, price, or browser operation.'),
            self::source('USER-PASTED-POB', 'User-pasted PoB or PoB2 code', SourceType::UserSupplied, AccessMode::PastedText, 'Text deliberately submitted by a user; no URL fetch.'),
            self::source('USER-PASTED-ITEM', 'User-pasted item text', SourceType::UserSupplied, AccessMode::PastedText, 'Item text deliberately submitted by a user.'),
            self::source('GGG-DOCUMENTED-API', 'Official documented GGG APIs', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Only exact operations in the official API Reference can ever be reviewed.'),
            self::source('GGG-APPLICATION-REGISTRATION', 'GGG application registration', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Official registration status; no registration attempt is implemented.'),
            self::source('GGG-UNDOCUMENTED-TRADE', 'Undocumented GGG Trade endpoints', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Internal Trade-site endpoints outside the supported API Reference.'),
            self::source('GGG-ACCOUNT-SECRETS', 'GGG account and session secrets', SourceType::UserSupplied, AccessMode::PastedText, 'Credentials and session material Lootwright must never request or capture.'),
            self::source('GGG-SCRAPING', 'Official site, forum, and Trade scraping', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Automated extraction from GGG web properties.'),
            self::source('GGG-CLIENT-AUTOMATION', 'Client, overlay, macro, and automation interaction', SourceType::ThirdPartySite, AccessMode::LocalUpload, 'Game/client/browser inspection or automated interaction.'),
            self::source('POBBIN-REMOTE', 'Remote pobb.in builds', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Remote fetching is distinct from user-pasted share codes.'),
            self::source('POB-COMMUNITY', 'Path of Building Community', SourceType::OpenSourceProject, AccessMode::RemoteFetch, 'Candidate source-code and format reference; embedded third-party rights remain unreviewed.'),
            self::source('POB2-COMMUNITY', 'Path of Building Community for PoE2', SourceType::OpenSourceProject, AccessMode::RemoteFetch, 'Format-only beta reference; no game data, code, or remote access.'),
            self::source('REPOE-CANDIDATE', 'RePoE or similar generated datasets', SourceType::CommunityDataset, AccessMode::RemoteFetch, 'Candidate generated game data with unresolved underlying rights.'),
            self::source('GGG-PROTECTED-ASSETS', 'GGG protected media and expression', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Artwork, images, logos, music, flavour text, screenshots, and fonts.'),
            self::source('OPENAI-API', 'OpenAI API', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Optional provider remains disabled until an explicit reviewed provider decision.'),
            self::source('LOOTWRIGHT-FUNDING', 'Donations and monetized hosting', SourceType::ThirdPartySite, AccessMode::AuthenticatedApi, 'Funding and monetized hosting are disabled pending explicit review.'),
        ];
    }

    /** @return list<array<string, string>> */
    public static function versions(): array
    {
        return [
            ['source_id' => 'LOOTWRIGHT-MANUAL-TRADE', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'USER-PASTED-POB', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'USER-PASTED-ITEM', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-DOCUMENTED-API', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-APPLICATION-REGISTRATION', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-UNDOCUMENTED-TRADE', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-ACCOUNT-SECRETS', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-SCRAPING', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-CLIENT-AUTOMATION', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POBBIN-REMOTE', 'version' => 'unreviewed-2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POB-COMMUNITY', 'version' => 'bcbca9b60b04abc17935c84ff3589342193bd758', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POB2-COMMUNITY', 'version' => '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'REPOE-CANDIDATE', 'version' => 'unreviewed-2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-PROTECTED-ASSETS', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'OPENAI-API', 'version' => '2026-08-15', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'LOOTWRIGHT-FUNDING', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function evidence(): array
    {
        return [
            self::evidenceRecord('LOOTWRIGHT-MANUAL-TRADE-EVIDENCE', 'LOOTWRIGHT-MANUAL-TRADE', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/product/manual-trade-workflow.md', PermissionStatus::Allowed, 'Lootwright-original plain-text recipes may be generated locally from approved immutable vocabulary without market access or automation.', false),
            self::evidenceRecord('USER-PASTED-POB-EVIDENCE', 'USER-PASTED-POB', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/capability-matrix.md', PermissionStatus::Allowed, 'Lootwright policy permits bounded processing of deliberately pasted input.', false),
            self::evidenceRecord('USER-PASTED-ITEM-EVIDENCE', 'USER-PASTED-ITEM', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/capability-matrix.md', PermissionStatus::Allowed, 'Lootwright policy permits bounded processing of deliberately pasted item text.', false),
            self::evidenceRecord('GGG-DEVELOPER-DOCS-20260814', 'GGG-DOCUMENTED-API', '2026-08-14', 'https://www.pathofexile.com/developer/docs', PermissionStatus::Allowed, 'Official policy reference is current, but no API operation is approved.', true),
            self::evidenceRecord('GGG-REGISTRATION-20260814', 'GGG-APPLICATION-REGISTRATION', '2026-08-14', 'https://www.pathofexile.com/developer/docs', PermissionStatus::Denied, 'GGG states it is currently unable to process new applications.', true),
            self::evidenceRecord('GGG-API-REFERENCE-20260814', 'GGG-UNDOCUMENTED-TRADE', '2026-08-14', 'https://www.pathofexile.com/developer/docs/reference', PermissionStatus::Denied, 'The supported API Reference does not list the internal Trade endpoints.', true),
            self::evidenceRecord('GGG-TERMS-SECRETS-20260814', 'GGG-ACCOUNT-SECRETS', '2026-08-14', 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy', PermissionStatus::Denied, 'Lootwright never accepts account credentials or session secrets.', true),
            self::evidenceRecord('GGG-TERMS-SCRAPING-20260814', 'GGG-SCRAPING', '2026-08-14', 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy', PermissionStatus::Denied, 'The Terms prohibit automated extraction without prior written approval.', true),
            self::evidenceRecord('GGG-TERMS-CLIENT-20260814', 'GGG-CLIENT-AUTOMATION', '2026-08-14', 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy', PermissionStatus::Denied, 'Client modification, unauthorized connections, bots, and reverse engineering are prohibited.', true),
            self::evidenceRecord('POBBIN-PERMISSION-UNKNOWN', 'POBBIN-REMOTE', 'unreviewed-2026-08-14', 'https://pobb.in/', PermissionStatus::Unknown, 'No explicit remote-fetch permission evidence has been recorded.', false),
            self::evidenceRecord('POB-COMMUNITY-LICENSE-20260814', 'POB-COMMUNITY', 'bcbca9b60b04abc17935c84ff3589342193bd758', 'https://github.com/PathOfBuildingCommunity/PathOfBuilding/blob/bcbca9b60b04abc17935c84ff3589342193bd758/LICENSE.md', PermissionStatus::Allowed, 'MIT evidence permits independently implemented format interoperability only; all broader reuse remains under review.', true, 'Attribute Path of Building Community and link its MIT license.'),
            self::evidenceRecord('POB2-COMMUNITY-LICENSE-20260814', 'POB2-COMMUNITY', '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6', 'https://github.com/PathOfBuildingCommunity/PathOfBuilding-PoE2/blob/5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6/LICENSE.md', PermissionStatus::Allowed, 'MIT evidence permits independently implemented beta format interoperability only.', true, 'Attribute Path of Building Community and link its MIT license.'),
            self::evidenceRecord('REPOE-RIGHTS-UNKNOWN', 'REPOE-CANDIDATE', 'unreviewed-2026-08-14', 'https://github.com/brather1ng/RePoE', PermissionStatus::Unknown, 'Repository accessibility does not establish rights in generated underlying game data.', true),
            self::evidenceRecord('GGG-TERMS-ASSETS-20260814', 'GGG-PROTECTED-ASSETS', '2026-08-14', 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy', PermissionStatus::Denied, 'Protected GGG media and expression are outside Lootwright redistribution rights.', true),
            self::evidenceRecord('OPENAI-DATA-CONTROLS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/your-data', PermissionStatus::Allowed, 'Official OpenAI documentation describes API data use and retention controls.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-RESPONSES-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/reference/resources/responses/methods/create', PermissionStatus::Allowed, 'Official OpenAI API reference documents POST /v1/responses and its stateless request controls.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-STRUCTURED-OUTPUTS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/structured-outputs', PermissionStatus::Allowed, 'Official OpenAI documentation defines strict JSON Schema Structured Outputs and explicit refusals.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-GPT54-NANO-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/models/gpt-5.4-nano', PermissionStatus::Allowed, 'Official model documentation confirms Responses API and Structured Outputs support.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-PRICING-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/pricing', PermissionStatus::Allowed, 'Official pricing documents the configured GPT-5.4 nano token prices.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-SPEND-LIMITS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/spend-limits', PermissionStatus::Allowed, 'Official OpenAI documentation describes enforceable organization and project spend limits.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('LOOTWRIGHT-FUNDING-DENIAL', 'LOOTWRIGHT-FUNDING', '2026-08-14', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/funding-policy.md', PermissionStatus::Denied, 'Funding and monetized hosting remain disabled pending explicit review.', false),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function rules(): array
    {
        $rules = [];
        $userConditions = ['explicit_user_submission'];
        $rules[] = self::rule('LOOTWRIGHT-MANUAL-TRADE', '1.0.0', Capability::DerivativeAnalysis, 'trade.manual_recipe.generate', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['deterministic_input', 'exact_ruleset_resolved', 'manual_actions_only', 'no_market_data'], 'A local plain-text recipe may be generated only from deterministic recommendations and approved exact ruleset vocabulary.');
        $rules[] = self::rule('LOOTWRIGHT-MANUAL-TRADE', '1.0.0', Capability::LinkOut, 'trade.homepage.link', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['explicit_user_action', 'generic_homepage_only', 'single_link_only'], 'One clearly labelled generic official Trade homepage link is allowed for manual use.');
        $rules[] = self::rule('LOOTWRIGHT-MANUAL-TRADE', '1.0.0', Capability::LinkOut, 'trade.encoded_url.generate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Encoded or query-bearing official Trade search URLs are prohibited.');
        $rules[] = self::rule('LOOTWRIGHT-MANUAL-TRADE', '1.0.0', Capability::LiveFetch, 'trade.listings.fetch', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Manual recipe generation cannot fetch, rank, cache, monitor, or price live listings.');

        foreach ([
            'USER-PASTED-POB' => 'pob_code',
            'USER-PASTED-ITEM' => 'item_text',
        ] as $source => $operation) {
            $rules[] = self::rule($source, '1.0.0', Capability::Import, "user_input.{$operation}.import", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $userConditions, 'Deliberately pasted input may enter the bounded intake workflow.');
            $rules[] = self::rule($source, '1.0.0', Capability::TransientProcess, "user_input.{$operation}.process", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $userConditions, 'Deliberately pasted input may be processed transiently with hostile-input limits.');
            $rules[] = self::rule($source, '1.0.0', Capability::PersistentStore, "user_input.{$operation}.store", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, [...$userConditions, 'user_storage_consent', 'authenticated_user'], 'User-owned persistence requires authentication, explicit storage consent, and retention controls.');
            $rules[] = self::rule($source, '1.0.0', Capability::PublicDisplay, "user_input.{$operation}.public_display", PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'User input is private by default and cannot be published.');
            $rules[] = self::rule($source, '1.0.0', Capability::Redistribution, "user_input.{$operation}.redistribute", PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Public redistribution of user input is denied by default.');
        }

        $rules[] = self::rule('GGG-DOCUMENTED-API', '2026-08-14', Capability::LiveFetch, 'ggg.api.documented_operation', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['application_registration', 'configured_credentials', 'current_policy_evidence', 'least_privilege_scopes'], 'No documented GGG API operation is enabled; each exact method and path requires a reviewed rule.');
        $rules[] = self::rule('GGG-APPLICATION-REGISTRATION', '2026-08-14', Capability::LiveFetch, 'ggg.application.register', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'GGG currently states that it is unable to process new application registrations.');

        foreach (['get:/api/trade/search', 'get:/api/trade/fetch', 'family:/api/trade/data'] as $operation) {
            $rules[] = self::rule('GGG-UNDOCUMENTED-TRADE', '2026-08-14', Capability::LiveFetch, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'This internal Trade endpoint is outside the supported API Reference and is denied.');
        }

        foreach (['credential.poesessid', 'credential.password', 'credential.browser_cookie', 'credential.session_capture'] as $operation) {
            $rules[] = self::rule('GGG-ACCOUNT-SECRETS', '2026-08-14', Capability::Import, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Account credentials, cookies, and session material are always denied.');
            $rules[] = self::rule('GGG-ACCOUNT-SECRETS', '2026-08-14', Capability::PersistentStore, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Account credentials, cookies, and session material may never be stored.');
        }

        foreach (['scrape.official_site', 'scrape.forum', 'scrape.trade_site'] as $operation) {
            $rules[] = self::rule('GGG-SCRAPING', '2026-08-14', Capability::LiveFetch, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Official site, forum, and Trade scraping are denied.');
        }

        foreach (['client.file', 'client.memory', 'client.network', 'client.screen', 'client.log', 'browser.extension', 'overlay.executable', 'automation.macro', 'automation.input', 'automation.trade'] as $operation) {
            $rules[] = self::rule('GGG-CLIENT-AUTOMATION', '2026-08-14', Capability::TransientProcess, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Client inspection, overlays, macros, and automated interaction are denied.');
        }

        $rules[] = self::rule('POBBIN-REMOTE', 'unreviewed-2026-08-14', Capability::LiveFetch, 'pobbin.fetch', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['current_permission_evidence', 'explicit_remote_fetch_consent'], 'Remote pobb.in fetching remains disabled until explicit permission evidence is reviewed.');

        foreach ([Capability::Import, Capability::DerivativeAnalysis, Capability::Redistribution] as $capability) {
            $rules[] = self::rule('POB-COMMUNITY', 'bcbca9b60b04abc17935c84ff3589342193bd758', $capability, 'pob.community.reuse', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['attribution_configured', 'mit_license_verified', 'pinned_repository_version', 'third_party_license_review'], 'Reuse requires the pinned repository version, MIT evidence, attribution, and third-party-license review.');
        }
        $formatConditions = ['attribution_configured', 'independent_implementation', 'pinned_repository_version'];
        $rules[] = self::rule('POB-COMMUNITY', 'bcbca9b60b04abc17935c84ff3589342193bd758', Capability::DerivativeAnalysis, 'pob.community.format_interpret', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $formatConditions, 'Only independent, local interpretation of the pinned PoB1 format is allowed.');
        $rules[] = self::rule('POB2-COMMUNITY', '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6', Capability::DerivativeAnalysis, 'pob2.community.format_interpret', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $formatConditions, 'Only independent, local beta interpretation of the pinned PoB2 format is allowed.');

        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'repoe.dataset.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['current_permission_evidence', 'underlying_data_rights_allowed'], 'Generated datasets remain disabled until underlying data rights and GGG policy are documented.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::DerivativeAnalysis, 'repoe.dataset.analyse', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['current_permission_evidence', 'underlying_data_rights_allowed'], 'Derived analysis remains disabled until underlying rights are documented.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Redistribution, 'repoe.dataset.hosted_redistribution', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Hosted redistribution is denied while underlying data rights are unresolved.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::MonetizedHosting, 'repoe.dataset.monetized_hosting', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Monetized hosting is denied while underlying data rights are unresolved.');

        foreach (['asset.art', 'asset.item_image', 'asset.logo', 'asset.music', 'asset.flavour_text', 'asset.screenshot', 'asset.font'] as $operation) {
            $rules[] = self::rule('GGG-PROTECTED-ASSETS', '2026-08-14', Capability::PublicDisplay, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'GGG protected art, media, screenshots, and fonts are denied.');
        }
        $rules[] = self::rule('GGG-PROTECTED-ASSETS', '2026-08-14', Capability::Redistribution, 'asset.redistribute', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Redistribution of protected GGG expression is denied.');

        $openAiConditions = ['configured_credentials', 'current_policy_evidence', 'data_minimization', 'privacy_disclosure', 'provider_approved', 'spend_limit_configured'];
        $rules[] = self::rule('OPENAI-API', '2026-08-15', Capability::LiveFetch, 'openai.responses.intent', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $openAiConditions, 'The tested adapter exists but remains non-executable until privacy disclosure, opt-in UX, provider approval, and deployment spend controls are reviewed.');
        $rules[] = self::rule('OPENAI-API', '2026-08-15', Capability::LiveFetch, 'openai.responses.explanation', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $openAiConditions, 'The tested adapter exists but remains non-executable until privacy disclosure, opt-in UX, provider approval, and deployment spend controls are reviewed.');

        $rules[] = self::rule('LOOTWRIGHT-FUNDING', '2026-08-14', Capability::MonetizedHosting, 'lootwright.donations', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Donations remain disabled pending explicit policy and legal review.');
        $rules[] = self::rule('LOOTWRIGHT-FUNDING', '2026-08-14', Capability::MonetizedHosting, 'lootwright.hosting', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Monetized hosting remains disabled pending explicit policy and legal review.');

        return $rules;
    }

    /** @return array<string, string> */
    private static function source(
        string $id,
        string $name,
        SourceType $type,
        AccessMode $accessMode,
        string $description,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'source_type' => $type->value,
            'access_mode' => $accessMode->value,
            'description' => $description,
        ];
    }

    /** @return array<string, mixed> */
    private static function evidenceRecord(
        string $id,
        string $sourceId,
        string $sourceVersion,
        string $url,
        PermissionStatus $status,
        string $summary,
        bool $attributionRequired,
        ?string $attributionNotice = null,
        ?string $retrievedAt = null,
    ): array {
        return [
            'id' => $id,
            'source_id' => $sourceId,
            'source_version' => $sourceVersion,
            'evidence_url' => $url,
            'retrieved_at' => $retrievedAt ?? self::REVIEWED_AT,
            'effective_from' => '2026-08-14T00:00:00Z',
            'effective_until' => self::REVIEW_EXPIRES_AT,
            'permission_status' => $status->value,
            'attribution_required' => $attributionRequired,
            'attribution_notice' => $attributionRequired
                ? ($attributionNotice ?? 'Cite the named source and retain its required notices.')
                : null,
            'summary' => $summary,
            'reviewer_role' => 'compliance_reviewer',
        ];
    }

    /** @param list<string> $requiredConditions
     * @return array<string, mixed>
     */
    private static function rule(
        string $sourceId,
        string $sourceVersion,
        Capability $capability,
        string $operation,
        PolicyDecision $decision,
        PolicyDecisionReason $reason,
        array $requiredConditions,
        string $explanation,
    ): array {
        return [
            'source_id' => $sourceId,
            'source_version' => $sourceVersion,
            'capability' => $capability->value,
            'operation' => $operation,
            'decision' => $decision->value,
            'reason' => $reason->value,
            'required_conditions' => $requiredConditions,
            'explanation' => $explanation,
            'policy_version' => self::POLICY_VERSION,
        ];
    }
}
