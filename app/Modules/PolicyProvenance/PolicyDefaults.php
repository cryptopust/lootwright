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

    /** @return list<array<string, string|bool>> */
    public static function sources(): array
    {
        return [
            self::source('LOOTWRIGHT-MANUAL-TRADE', 'Lootwright manual Trade recipe schema', SourceType::FirstPartyOriginal, AccessMode::LocalRuntime, 'Original local-only recipe generation; no Trade endpoint, listing, price, or browser operation.'),
            self::source('USER-POB-001', 'User-submitted PoB code', SourceType::UserSupplied, AccessMode::PastedText, 'Canonical governed source for a PoB code deliberately submitted by its user.', 'allowed', true, 'active'),
            self::source('USER-ITEM-TEXT-001', 'User-submitted item text', SourceType::UserSupplied, AccessMode::PastedText, 'Canonical governed source for item text deliberately submitted by its user.', 'allowed', true, 'active'),
            self::source('GGG-POE1-SKILLTREE-001', 'Official PoE1 passive skill tree export', SourceType::OfficialDocumentedApi, AccessMode::RemoteFetch, 'Exact reviewed official PoE1 skill-tree export revisions only; imports run out of band.', 'allowed', true, 'active'),
            self::source('GGG-POE1-ATLASTREE-001', 'Official PoE1 Atlas passive tree export', SourceType::OfficialDocumentedApi, AccessMode::RemoteFetch, 'Reviewed official Atlas-tree family; intentionally outside the PoE1 MVP.', 'allowed', false, 'outside_mvp'),
            self::source('GGG-DOCUMENTED-API', 'Official documented GGG APIs', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Only exact operations in the official API Reference can ever be reviewed.'),
            self::source('GGG-APPLICATION-REGISTRATION', 'GGG application registration', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Official registration status; no registration attempt is implemented.'),
            self::source('GGG-UNDOCUMENTED-TRADE', 'Undocumented GGG Trade endpoints', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Internal Trade-site endpoints outside the supported API Reference.'),
            self::source('GGG-ACCOUNT-SECRETS', 'GGG account and session secrets', SourceType::UserSupplied, AccessMode::PastedText, 'Credentials and session material Lootwright must never request or capture.'),
            self::source('GGG-SCRAPING', 'Official site, forum, and Trade scraping', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Automated extraction from GGG web properties.'),
            self::source('GGG-CLIENT-AUTOMATION', 'Client, overlay, macro, and automation interaction', SourceType::ThirdPartySite, AccessMode::LocalUpload, 'Game/client/browser inspection or automated interaction.'),
            self::source('POBBIN-REMOTE', 'Remote pobb.in builds', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Remote fetching is distinct from user-pasted share codes.'),
            self::source('POB-COMMUNITY', 'Path of Building Community', SourceType::OpenSourceProject, AccessMode::RemoteFetch, 'Candidate source-code and format reference; embedded third-party rights remain unreviewed.'),
            self::source('POB2-COMMUNITY', 'Path of Building Community for PoE2', SourceType::OpenSourceProject, AccessMode::RemoteFetch, 'Format-only beta reference; no game data, code, or remote access.'),
            self::source('REPOE-CANDIDATE', 'RePoE or similar generated datasets', SourceType::CommunityDataset, AccessMode::RemoteFetch, 'Candidate generated game data with unresolved underlying rights.', 'prohibited', false, 'prohibited'),
            self::source('POENINJA-ECONOMY-001', 'poe.ninja public economy API lifecycle source', SourceType::ThirdPartySite, AccessMode::AnonymousHttp, 'Canonical lifecycle identity; conditional and disabled unless policy and configuration both permit an out-of-band import.', 'conditional', false, 'optional'),
            self::source('POEWIKI-CARGO-001', 'Path of Exile Wiki Cargo lifecycle source', SourceType::ThirdPartySite, AccessMode::AnonymousHttp, 'Canonical lifecycle identity; conditional and disabled pending the recorded rights review.', 'conditional', false, 'candidate'),
            self::source('GGG-PROTECTED-ASSETS', 'GGG protected media and expression', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Artwork, images, logos, music, flavour text, screenshots, and fonts.'),
            self::source('OPENAI-API', 'OpenAI API', SourceType::OfficialDocumentedApi, AccessMode::AuthenticatedApi, 'Optional provider remains disabled until an explicit reviewed provider decision.'),
            self::source('LOOTWRIGHT-FUNDING', 'Lootwright funding policy', SourceType::FirstPartyOriginal, AccessMode::LocalRuntime, 'Funding and monetized hosting are disabled pending explicit review.'),
        ];
    }

    /** @return list<array<string, string>> */
    public static function versions(): array
    {
        return [
            ['source_id' => 'LOOTWRIGHT-MANUAL-TRADE', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'USER-POB-001', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'USER-ITEM-TEXT-001', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-POE1-SKILLTREE-001', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-POE1-ATLASTREE-001', 'version' => '1.0.0', 'policy_version' => self::POLICY_VERSION],
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
            ['source_id' => 'POENINJA-ECONOMY-001', 'version' => 'economy-v1', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POEWIKI-CARGO-001', 'version' => 'candidate-2026-08-20', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'GGG-PROTECTED-ASSETS', 'version' => '2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'OPENAI-API', 'version' => '2026-08-15', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'LOOTWRIGHT-FUNDING', 'version' => '2026-08-15', 'policy_version' => self::POLICY_VERSION],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function evidence(): array
    {
        return [
            self::evidenceRecord('LOOTWRIGHT-MANUAL-TRADE-EVIDENCE', 'LOOTWRIGHT-MANUAL-TRADE', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/product/manual-trade-workflow.md', PermissionStatus::Allowed, 'Lootwright-original plain-text recipes may be generated locally from approved immutable vocabulary without market access or automation.', false),
            self::evidenceRecord('USER-POB-001-EVIDENCE', 'USER-POB-001', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/source-register.md', PermissionStatus::Allowed, 'A user may submit their own PoB text to the bounded private import workflow.', false),
            self::evidenceRecord('USER-ITEM-TEXT-001-EVIDENCE', 'USER-ITEM-TEXT-001', '1.0.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/source-register.md', PermissionStatus::Allowed, 'A user may submit their own item text to the bounded private import workflow.', false),
            self::evidenceRecord('GGG-POE1-SKILLTREE-001-EVIDENCE', 'GGG-POE1-SKILLTREE-001', '1.0.0', 'https://www.pathofexile.com/developer/docs/reference', PermissionStatus::Allowed, 'Only an exact documented official PoE1 skill-tree export family may be imported through a reviewed operator workflow.', true, 'This product is not affiliated with or endorsed by Grinding Gear Games.'),
            self::evidenceRecord('GGG-POE1-ATLASTREE-001-EVIDENCE', 'GGG-POE1-ATLASTREE-001', '1.0.0', 'https://www.pathofexile.com/developer/docs/reference', PermissionStatus::Allowed, 'The documented Atlas-tree source is recorded but outside the current PoE1 MVP.', true, 'This product is not affiliated with or endorsed by Grinding Gear Games.'),
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
            self::evidenceRecord('POENINJA-ECONOMY-LIFECYCLE-EVIDENCE', 'POENINJA-ECONOMY-001', 'economy-v1', 'https://poe.ninja/docs/api', PermissionStatus::Allowed, 'The canonical lifecycle identity inherits only the reviewed public PoE1 economy boundary and remains configuration-disabled.', true, 'Attribute poe.ninja as the market-context source where displayed.', '2026-08-20T00:00:00Z'),
            self::evidenceRecord('POEWIKI-CARGO-LIFECYCLE-EVIDENCE', 'POEWIKI-CARGO-001', 'candidate-2026-08-20', 'https://www.poewiki.net/wiki/Path_of_Exile_Wiki:Data_query_API', PermissionStatus::Unknown, 'The canonical lifecycle identity remains disabled pending licensing, redistribution, underlying-rights, and funding review.', true, 'Review CC BY-NC-SA attribution and share-alike requirements before activation.', '2026-08-20T00:00:00Z'),
            self::evidenceRecord('GGG-TERMS-ASSETS-20260814', 'GGG-PROTECTED-ASSETS', '2026-08-14', 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy', PermissionStatus::Denied, 'Protected GGG media and expression are outside Lootwright redistribution rights.', true),
            self::evidenceRecord('OPENAI-DATA-CONTROLS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/your-data', PermissionStatus::Allowed, 'Official OpenAI documentation describes API data use and retention controls.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-RESPONSES-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/reference/resources/responses/methods/create', PermissionStatus::Allowed, 'Official OpenAI API reference documents POST /v1/responses and its stateless request controls.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-STRUCTURED-OUTPUTS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/structured-outputs', PermissionStatus::Allowed, 'Official OpenAI documentation defines strict JSON Schema Structured Outputs and explicit refusals.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-GPT54-NANO-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/models/gpt-5.4-nano', PermissionStatus::Allowed, 'Official model documentation confirms Responses API and Structured Outputs support.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-PRICING-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/pricing', PermissionStatus::Allowed, 'Official pricing documents the configured GPT-5.4 nano token prices.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('OPENAI-SPEND-LIMITS-20260815', 'OPENAI-API', '2026-08-15', 'https://developers.openai.com/api/docs/guides/spend-limits', PermissionStatus::Allowed, 'Official OpenAI documentation describes enforceable organization and project spend limits.', false, retrievedAt: '2026-08-15T00:00:00Z'),
            self::evidenceRecord('LOOTWRIGHT-FUNDING-DENIAL', 'LOOTWRIGHT-FUNDING', '2026-08-15', 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/funding-policy.md', PermissionStatus::Denied, 'No legal approval, GGG approval, or support correspondence establishes permission; funding and monetized hosting remain disabled.', false, retrievedAt: '2026-08-15T20:15:22Z'),
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
            'USER-POB-001' => 'pob_code',
            'USER-ITEM-TEXT-001' => 'item_text',
        ] as $source => $operation) {
            $rules[] = self::rule($source, '1.0.0', Capability::Import, "user_input.{$operation}.import", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $userConditions, 'Deliberately pasted input may enter the bounded intake workflow.');
            $rules[] = self::rule($source, '1.0.0', Capability::TransientProcess, "user_input.{$operation}.process", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $userConditions, 'Deliberately pasted input may be processed transiently with hostile-input limits.');
            $rules[] = self::rule($source, '1.0.0', Capability::PersistentStore, "user_input.{$operation}.store", PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, [...$userConditions, 'user_storage_consent', 'authenticated_user'], 'User-owned persistence requires authentication, explicit storage consent, and retention controls.');
            $rules[] = self::rule($source, '1.0.0', Capability::PublicDisplay, "user_input.{$operation}.public_display", PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'User input is private by default and cannot be published.');
            $rules[] = self::rule($source, '1.0.0', Capability::Redistribution, "user_input.{$operation}.redistribute", PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Public redistribution of user input is denied by default.');
        }

        foreach ([
            'USER-POB-001' => 'user.pob.snapshot.import',
            'USER-ITEM-TEXT-001' => 'user.item_text.snapshot.import',
        ] as $source => $operation) {
            $rules[] = self::rule($source, '1.0.0', Capability::Import, $operation, PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['explicit_user_submission'], 'A bounded normalized snapshot of deliberately submitted user input may be imported with separate provenance.');
        }
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '1.0.0', Capability::Import, 'ggg.poe1.skilltree.snapshot.import', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['documented_export', 'operator_workflow', 'checksum_verified', 'poe1_scope'], 'A reviewed official PoE1 skill-tree export may be imported only out of band.');
        $rules[] = self::rule('GGG-POE1-ATLASTREE-001', '1.0.0', Capability::Import, 'ggg.poe1.atlastree.snapshot.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['documented_export', 'operator_workflow', 'checksum_verified', 'poe1_scope'], 'The official Atlas-tree family is allowed in principle but remains outside the MVP runtime scope.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'poewiki.cargo.snapshot.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['license_chain_reviewed', 'ggg_data_rights_reviewed', 'funding_reviewed', 'exact_field_allowlist'], 'PoE Wiki Cargo imports remain conditional and disabled.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'poeninja.economy.snapshot.import', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['operator_contact_configured', 'exact_endpoint_allowlist', 'normalized_snapshot_only'], 'A normalized poe.ninja economy snapshot is conditional on the independent source switch and exact Policy Gate decision.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'repoe.snapshot.import', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'RePoE is prohibited as a production snapshot source until a new reviewed source decision supersedes this denial.');

        foreach (['USER-POB-001', 'USER-ITEM-TEXT-001'] as $source) {
            $rules[] = self::rule($source, '1.0.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Private user input can never become ruleset authority.');
        }
        $activationConditions = ['checksum_verified', 'immutable_snapshot', 'poe1_scope'];
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '1.0.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $activationConditions, 'A verified immutable official PoE1 skill-tree snapshot may support a published ruleset.');
        $rules[] = self::rule('GGG-POE1-ATLASTREE-001', '1.0.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Atlas-tree rules are outside the current MVP activation scope.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'ruleset.source.activate', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $activationConditions, 'PoE Wiki Cargo cannot become ruleset authority before the separate rights review.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Market context is never deterministic game-rule authority.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'RePoE cannot become production ruleset authority under the current decision.');

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

        foreach (['poe_ninja.economy.leagues.fetch', 'poe_ninja.economy.exchange.fetch', 'poe_ninja.economy.stash_item.fetch'] as $operation) {
            $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::LiveFetch, $operation, PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['operator_contact_configured', 'https_only', 'exact_endpoint_allowlist'], 'Only the documented, exact PoE1 economy endpoint family is enabled behind the source switch and Policy Gate.');
        }
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::DerivativeAnalysis, 'poe_ninja.economy.normalized_quote.read', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['normalized_snapshot_only'], 'Analysis may consume immutable normalized market evidence with source and freshness.');
        foreach (['poe_ninja.builds.fetch', 'poe_ninja.profiles.fetch', 'poe_ninja.characters.fetch', 'poe_ninja.pob.fetch', 'poe_ninja.authentication.fetch', 'poe_ninja.page.scrape', 'poe_ninja.site.replicate'] as $operation) {
            $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::LiveFetch, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'This poe.ninja operation is outside the approved public economy API boundary.');
        }
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::LiveFetch, 'poe_wiki.cargo.factual_metadata.fetch', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['license_chain_reviewed', 'ggg_data_rights_reviewed', 'funding_reviewed', 'exact_field_allowlist'], 'The disabled Cargo adapter has no production fetch authority.');

        foreach (['asset.art', 'asset.item_image', 'asset.logo', 'asset.music', 'asset.flavour_text', 'asset.screenshot', 'asset.font'] as $operation) {
            $rules[] = self::rule('GGG-PROTECTED-ASSETS', '2026-08-14', Capability::PublicDisplay, $operation, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'GGG protected art, media, screenshots, and fonts are denied.');
        }
        $rules[] = self::rule('GGG-PROTECTED-ASSETS', '2026-08-14', Capability::Redistribution, 'asset.redistribute', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Redistribution of protected GGG expression is denied.');

        $openAiConditions = ['configured_credentials', 'current_policy_evidence', 'data_minimization', 'privacy_disclosure', 'provider_approved', 'spend_limit_configured'];
        $rules[] = self::rule('OPENAI-API', '2026-08-15', Capability::LiveFetch, 'openai.responses.intent', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $openAiConditions, 'The tested adapter exists but remains non-executable until privacy disclosure, opt-in UX, provider approval, and deployment spend controls are reviewed.');
        $rules[] = self::rule('OPENAI-API', '2026-08-15', Capability::LiveFetch, 'openai.responses.explanation', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $openAiConditions, 'The tested adapter exists but remains non-executable until privacy disclosure, opt-in UX, provider approval, and deployment spend controls are reviewed.');

        $rules[] = self::rule('LOOTWRIGHT-FUNDING', '2026-08-15', Capability::MonetizedHosting, 'lootwright.funding.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, ['dated_policy_decision', 'permission_evidence_recorded', 'operator_activation', 'public_disclosure_versioned', 'funding_equality_permanent', 'visible_disclosure'], 'The operator switch cannot activate funding without a superseding reviewed allow rule and allowed evidence.');
        $rules[] = self::rule('LOOTWRIGHT-FUNDING', '2026-08-15', Capability::MonetizedHosting, 'lootwright.donations', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Donations remain disabled pending explicit policy and legal review.');
        $rules[] = self::rule('LOOTWRIGHT-FUNDING', '2026-08-15', Capability::MonetizedHosting, 'lootwright.hosting', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Monetized hosting remains disabled pending explicit policy and legal review.');

        return $rules;
    }

    /** @return array<string, string|bool> */
    private static function source(
        string $id,
        string $name,
        SourceType $type,
        AccessMode $accessMode,
        string $description,
        string $governanceStatus = 'conditional',
        bool $enabledByDefault = false,
        string $mvpScope = 'candidate',
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'source_type' => $type->value,
            'access_mode' => $accessMode->value,
            'description' => $description,
            'governance_status' => $governanceStatus,
            'enabled_by_default' => $enabledByDefault,
            'mvp_scope' => $mvpScope,
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
