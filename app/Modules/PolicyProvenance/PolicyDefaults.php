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
    public const POLICY_VERSION = '1.3.0';

    public const REVIEWED_AT = '2026-08-21T00:00:00Z';

    public const REVIEW_EXPIRES_AT = '2026-11-12T00:00:00Z';

    private function __construct() {}

    /** @return list<array<string, string|bool>> */
    public static function sources(): array
    {
        return [
            self::source('LOOTWRIGHT-MANUAL-TRADE', 'Lootwright manual Trade recipe schema', SourceType::FirstPartyOriginal, AccessMode::LocalRuntime, 'Original local-only recipe generation; no Trade endpoint, listing, price, or browser operation.'),
            self::source('USER-POB-001', 'User-submitted PoB code', SourceType::UserSupplied, AccessMode::PastedText, 'Canonical governed source for a PoB code deliberately submitted by its user.', 'allowed', true, 'active'),
            self::source('USER-ITEM-TEXT-001', 'User-submitted item text', SourceType::UserSupplied, AccessMode::PastedText, 'Canonical governed source for item text deliberately submitted by its user.', 'allowed', true, 'active'),
            self::source('GGG-POE1-SKILLTREE-001', 'Official PoE1 passive skill tree export', SourceType::OfficialDocumentedApi, AccessMode::RemoteFetch, 'Exact reviewed commit-pinned grindinggear/skilltree-export data.json revisions only; imports run out of band.', 'allowed', false, 'active'),
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
            self::source('POB-GAME-DATA-CANDIDATE', 'Path of Building Community embedded PoE1 data', SourceType::CommunityDataset, AccessMode::RemoteFetch, 'Candidate PoE1 dataset capability distinct from the approved format-interoperability capability.', 'conditional', false, 'candidate'),
            self::source('POB2-GAME-DATA-CANDIDATE', 'Path of Building Community embedded PoE2 data', SourceType::CommunityDataset, AccessMode::RemoteFetch, 'Candidate PoE2 dataset capability distinct from the approved beta format-interoperability capability.', 'conditional', false, 'future'),
            self::source('REPOE-CANDIDATE', 'RePoE or similar generated datasets', SourceType::CommunityDataset, AccessMode::RemoteFetch, 'Candidate generated game data with unresolved underlying rights.', 'prohibited', false, 'prohibited'),
            self::source('DAT-SCHEMA-CANDIDATE', 'poe-tool-dev dat-schema', SourceType::OpenSourceProject, AccessMode::RemoteFetch, 'MIT-licensed table schema only; it contains no approved game rows and is not a canonical fact source.', 'conditional', false, 'candidate'),
            self::source('PYPOE-CANDIDATE', 'PyPoE and GGPK-derived tooling', SourceType::OpenSourceProject, AccessMode::LocalUpload, 'Client-file extraction tooling is outside the production data path and cannot run against user game files.', 'prohibited', false, 'prohibited'),
            self::source('POENINJA-ECONOMY-001', 'poe.ninja public economy API lifecycle source', SourceType::ThirdPartySite, AccessMode::AnonymousHttp, 'Canonical lifecycle identity; conditional and disabled unless policy and configuration both permit an out-of-band import.', 'conditional', false, 'optional'),
            self::source('POEWIKI-CARGO-001', 'Path of Exile Wiki Cargo lifecycle source', SourceType::ThirdPartySite, AccessMode::AnonymousHttp, 'Canonical lifecycle identity; conditional and disabled pending the recorded rights review.', 'conditional', false, 'candidate'),
            self::source('POE2-DATASET-CANDIDATE', 'Lootwright-authored PoE2 canonical fixture dataset', SourceType::CommunityDataset, AccessMode::LocalRuntime, 'Independent PoE2 facts authored for the reviewed 0.3.0 ruleset; no PoE1 mechanics or identifiers.', 'allowed', false, 'active'),
            self::source('POE-DB-CANDIDATE', 'PoEDB community database', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Reference candidate only; no reviewed API, licence, or redistribution permission.', 'conditional', false, 'candidate'),
            self::source('CRAFT-OF-EXILE-CANDIDATE', 'Craft of Exile crafting reference', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Reference candidate only; scraping and hosted extraction are disabled.', 'prohibited', false, 'prohibited'),
            self::source('POE-TRADE-VOCABULARY-CANDIDATE', 'Official Trade vocabulary/internal data paths', SourceType::ThirdPartySite, AccessMode::RemoteFetch, 'Undocumented Trade paths are permanently prohibited; manual recipes use Lootwright vocabulary.', 'prohibited', false, 'prohibited'),
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
            ['source_id' => 'GGG-POE1-SKILLTREE-001', 'version' => '8bd138b32ea2631455cac5935bfab089f826094f', 'policy_version' => self::POLICY_VERSION],
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
            ['source_id' => 'POB-GAME-DATA-CANDIDATE', 'version' => '510e03806791db5fb6563ef93104f2a62a273b97', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POB2-GAME-DATA-CANDIDATE', 'version' => '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'REPOE-CANDIDATE', 'version' => 'unreviewed-2026-08-14', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'DAT-SCHEMA-CANDIDATE', 'version' => '73ae93b30c1fe6b1e159cad8414349391cc0aac4', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'PYPOE-CANDIDATE', 'version' => 'unreviewed-2026-08-21', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POENINJA-ECONOMY-001', 'version' => 'economy-v1', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POEWIKI-CARGO-001', 'version' => 'candidate-2026-08-20', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POE2-DATASET-CANDIDATE', 'version' => 'poe2-0.3.0', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POE-DB-CANDIDATE', 'version' => 'candidate-2026-08-25', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'CRAFT-OF-EXILE-CANDIDATE', 'version' => 'candidate-2026-08-25', 'policy_version' => self::POLICY_VERSION],
            ['source_id' => 'POE-TRADE-VOCABULARY-CANDIDATE', 'version' => '2026-08-25', 'policy_version' => self::POLICY_VERSION],
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
            self::evidenceRecord('GGG-POE1-SKILLTREE-001-EVIDENCE', 'GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', 'https://github.com/grindinggear/skilltree-export/tree/8bd138b32ea2631455cac5935bfab089f826094f', PermissionStatus::Allowed, 'Only commit 8bd138b32ea2631455cac5935bfab089f826094f and its root data.json may be imported; raw Git blob checksum 7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122 is mandatory.', true, 'Source: Grinding Gear Games skilltree-export. This product is not affiliated with or endorsed by Grinding Gear Games.', '2026-08-20T00:00:00Z'),
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
            self::evidenceRecord('POB-GAME-DATA-RIGHTS-REVIEW-20260821', 'POB-GAME-DATA-CANDIDATE', '510e03806791db5fb6563ef93104f2a62a273b97', 'https://github.com/PathOfBuildingCommunity/PathOfBuilding/tree/510e03806791db5fb6563ef93104f2a62a273b97/src/Data', PermissionStatus::Unknown, 'The repository license review covers software, but no separate production redistribution decision was established for embedded or generated GGG game data.', true),
            self::evidenceRecord('POB2-GAME-DATA-RIGHTS-REVIEW-20260821', 'POB2-GAME-DATA-CANDIDATE', '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6', 'https://github.com/PathOfBuildingCommunity/PathOfBuilding-PoE2/tree/5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6/src/Data', PermissionStatus::Unknown, 'The beta project is technically useful, but embedded PoE2 facts require a separate rights, provenance, patch and redistribution review.', true),
            self::evidenceRecord('REPOE-RIGHTS-UNKNOWN', 'REPOE-CANDIDATE', 'unreviewed-2026-08-14', 'https://github.com/brather1ng/RePoE', PermissionStatus::Unknown, 'Repository accessibility does not establish rights in generated underlying game data.', true),
            self::evidenceRecord('DAT-SCHEMA-MIT-20260821', 'DAT-SCHEMA-CANDIDATE', '73ae93b30c1fe6b1e159cad8414349391cc0aac4', 'https://github.com/poe-tool-dev/dat-schema/tree/73ae93b30c1fe6b1e159cad8414349391cc0aac4', PermissionStatus::Allowed, 'The MIT-licensed schema may inform a separately reviewed importer contract; it contains table structure, not approved canonical game rows.', true, 'Attribute poe-tool-dev and retain its MIT license.'),
            self::evidenceRecord('PYPOE-CLIENT-EXTRACTION-DENIAL-20260821', 'PYPOE-CANDIDATE', 'unreviewed-2026-08-21', 'https://github.com/OmegaK2/PyPoE', PermissionStatus::Denied, 'Production use would require reading client files and derived game data; that capability is prohibited by the current security and source constitution.', true),
            self::evidenceRecord('POENINJA-ECONOMY-LIFECYCLE-EVIDENCE', 'POENINJA-ECONOMY-001', 'economy-v1', 'https://poe.ninja/docs/api', PermissionStatus::Allowed, 'The canonical lifecycle identity inherits only the reviewed public PoE1 economy boundary and remains configuration-disabled.', true, 'Attribute poe.ninja as the market-context source where displayed.', '2026-08-20T00:00:00Z'),
            self::evidenceRecord('POEWIKI-CARGO-LIFECYCLE-EVIDENCE', 'POEWIKI-CARGO-001', 'candidate-2026-08-20', 'https://www.poewiki.net/wiki/Path_of_Exile_Wiki:Data_query_API', PermissionStatus::Unknown, 'The canonical lifecycle identity remains disabled pending licensing, redistribution, underlying-rights, and funding review.', true, 'Review CC BY-NC-SA attribution and share-alike requirements before activation.', '2026-08-20T00:00:00Z'),
            self::evidenceRecord('POE2-DATASET-CANDIDATE-EVIDENCE', 'POE2-DATASET-CANDIDATE', 'poe2-0.3.0', 'https://github.com/cryptopust/lootwright/blob/main/docs/adr/0026-independent-poe2-deterministic-analysis.md', PermissionStatus::Allowed, 'Lootwright-authored, edition-isolated PoE2 canonical records and deterministic manifest for patch 0.3.0.', false, 'This product is not affiliated with or endorsed by Grinding Gear Games.'),
            self::evidenceRecord('POE-DB-CANDIDATE-EVIDENCE', 'POE-DB-CANDIDATE', 'candidate-2026-08-25', 'https://poedb.tw/', PermissionStatus::Unknown, 'Technical availability was confirmed, but no API, licence, cache, redistribution, or commercial-use approval is recorded.', false),
            self::evidenceRecord('CRAFT-OF-EXILE-CANDIDATE-EVIDENCE', 'CRAFT-OF-EXILE-CANDIDATE', 'candidate-2026-08-25', 'https://www.craftofexile.com/', PermissionStatus::Denied, 'No approved API/data licence is recorded; scraping and hosted extraction remain disabled.', false),
            self::evidenceRecord('POE-TRADE-VOCABULARY-CANDIDATE-EVIDENCE', 'POE-TRADE-VOCABULARY-CANDIDATE', '2026-08-25', 'https://www.pathofexile.com/developer/docs/reference', PermissionStatus::Denied, 'Undocumented internal Trade paths are outside the supported API boundary and permanently prohibited.', false),
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
        $skillTreeConditions = ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::Import, 'ggg.poe1.skilltree.snapshot.import', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $skillTreeConditions, 'Only the reviewed commit-pinned official PoE1 data.json export may be imported out of band.');
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::Import, 'ggg.poe1.skilltree.snapshot.quarantine', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'], 'A rejected exact-source candidate may record bounded immutable quarantine metadata without claiming checksum validation or storing the raw body.');
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::LiveFetch, 'ggg.poe1.skilltree.export.fetch', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['exact_url_allowlist', 'operator_contact_configured', 'operator_workflow', 'pinned_commit', 'poe1_scope'], 'Only the exact reviewed raw GitHub export URL may be fetched during an operator command.');
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::DerivativeAnalysis, 'ruleset.deterministic_analysis', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['checksum_verified', 'exact_ruleset_resolved', 'manual_actions_only'], 'A locally activated immutable GGG passive-tree snapshot may be consulted by deterministic PoE1 rules without request-time source access.');
        $rules[] = self::rule('GGG-POE1-ATLASTREE-001', '1.0.0', Capability::Import, 'ggg.poe1.atlastree.snapshot.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['documented_export', 'operator_workflow', 'checksum_verified', 'poe1_scope'], 'The official Atlas-tree family is allowed in principle but remains outside the MVP runtime scope.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'poewiki.cargo.snapshot.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['license_chain_reviewed', 'ggg_data_rights_reviewed', 'funding_reviewed', 'exact_field_allowlist'], 'PoE Wiki Cargo imports remain conditional and disabled.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'poeninja.economy.snapshot.import', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['operator_contact_configured', 'exact_endpoint_allowlist', 'normalized_snapshot_only'], 'A normalized poe.ninja economy snapshot is conditional on the independent source switch and exact Policy Gate decision.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'repoe.snapshot.import', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'RePoE is prohibited as a production snapshot source until a new reviewed source decision supersedes this denial.');
        $rules[] = self::rule('POE2-DATASET-CANDIDATE', 'poe2-0.3.0', Capability::Import, 'poe2.dataset.snapshot.import', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['approved_source_record', 'poe2_scope', 'checksum_verified'], 'The reviewed PoE2 canonical dataset may be imported through the operator workflow.');
        $rules[] = self::rule('POE-DB-CANDIDATE', 'candidate-2026-08-25', Capability::Import, 'poedb.snapshot.import', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, ['current_permission_evidence', 'exact_field_allowlist', 'cache_reviewed'], 'PoEDB is a reference candidate and has no canonical import authority.');
        $rules[] = self::rule('CRAFT-OF-EXILE-CANDIDATE', 'candidate-2026-08-25', Capability::Import, 'craftofexile.snapshot.import', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Craft of Exile scraping or hosted extraction is disabled.');
        $rules[] = self::rule('POE-TRADE-VOCABULARY-CANDIDATE', '2026-08-25', Capability::Import, 'trade.vocabulary.import', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Undocumented Trade vocabulary paths are prohibited; Lootwright uses only reviewed local vocabulary.');

        $rollbackConditions = ['authorized_actor', 'staging_only', 'no_canonical_mutation'];
        $approvalConditions = ['approved_snapshot', 'same_source', 'same_edition', 'no_canonical_mutation'];
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::Import, 'source.import.rollback', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $rollbackConditions, 'An authorized operator may discard staging records without mutating an immutable snapshot or canonical ruleset.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'source.import.rollback', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $rollbackConditions, 'An authorized operator may discard staging records without mutating approved market snapshots.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'source.import.rollback', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $rollbackConditions, 'The disabled Cargo source has no executable rollback workflow.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'source.import.rollback', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'The prohibited RePoE source has no executable import lifecycle.');
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::Import, 'source.import.approve', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $approvalConditions, 'Approval only links a valid same-edition immutable snapshot to staged records.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'source.import.approve', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, $approvalConditions, 'Approval only links a valid same-edition immutable snapshot to staged market records.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'source.import.approve', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $approvalConditions, 'The disabled Cargo source has no executable approval workflow.');

        foreach (['USER-POB-001', 'USER-ITEM-TEXT-001'] as $source) {
            $rules[] = self::rule($source, '1.0.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Private user input can never become ruleset authority.');
        }
        $activationConditions = ['checksum_verified', 'immutable_snapshot', 'poe1_scope'];
        $rules[] = self::rule('GGG-POE1-SKILLTREE-001', '8bd138b32ea2631455cac5935bfab089f826094f', Capability::Import, 'ruleset.source.activate', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, [...$activationConditions, 'official_repository', 'operator_workflow', 'pinned_commit'], 'A verified immutable commit-pinned official PoE1 skill-tree snapshot may support a published ruleset.');
        $rules[] = self::rule('GGG-POE1-ATLASTREE-001', '1.0.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Atlas-tree rules are outside the current MVP activation scope.');
        $rules[] = self::rule('POEWIKI-CARGO-001', 'candidate-2026-08-20', Capability::Import, 'ruleset.source.activate', PolicyDecision::RequireReview, PolicyDecisionReason::ReviewRequired, $activationConditions, 'PoE Wiki Cargo cannot become ruleset authority before the separate rights review.');
        $rules[] = self::rule('POENINJA-ECONOMY-001', 'economy-v1', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'Market context is never deterministic game-rule authority.');
        $rules[] = self::rule('REPOE-CANDIDATE', 'unreviewed-2026-08-14', Capability::Import, 'ruleset.source.activate', PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial, [], 'RePoE cannot become production ruleset authority under the current decision.');
        $rules[] = self::rule('POE2-DATASET-CANDIDATE', 'poe2-0.3.0', Capability::Import, 'ruleset.source.activate', PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, ['checksum_verified', 'immutable_snapshot', 'operator_workflow', 'poe2_scope'], 'The reviewed immutable PoE2 dataset may back its edition-scoped ruleset.');

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
            ...self::registryMetadata($id),
        ];
    }

    /** @return array<string, mixed> */
    private static function registryMetadata(string $id): array
    {
        $common = [
            'game_editions' => json_encode([], JSON_THROW_ON_ERROR),
            'reference_url' => null,
            'documentation_url' => null,
            'terms_url' => null,
            'redistribution_status' => 'unknown',
            'commercial_use_status' => 'unknown',
            'cache_storage_status' => 'prohibited',
            'last_policy_review_at' => self::REVIEWED_AT,
            'technical_access' => 'not_reviewed',
            'license_identifier' => 'NOASSERTION',
            'rate_limit_status' => 'unknown',
            'auth_requirements' => 'unknown',
            'data_quality_status' => 'unknown',
            'patch_versioning_status' => 'unknown',
            'update_frequency' => 'unknown',
            'provenance_status' => 'requires_review',
        ];

        return [...$common, ...match ($id) {
            'GGG-POE1-SKILLTREE-001' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://github.com/grindinggear/skilltree-export',
                'documentation_url' => 'https://github.com/grindinggear/skilltree-export',
                'terms_url' => 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy',
                'redistribution_status' => 'restricted',
                'commercial_use_status' => 'unknown',
                'cache_storage_status' => 'bounded',
                'technical_access' => 'operator_https_or_file',
                'license_identifier' => 'LicenseRef-GGG-Terms-of-Use',
                'rate_limit_status' => 'operator_pinned_fetch_only',
                'auth_requirements' => 'none',
                'data_quality_status' => 'official_structured',
                'patch_versioning_status' => 'git_commit',
                'update_frequency' => 'upstream_commit',
                'provenance_status' => 'approved_with_limits',
            ],
            'GGG-POE1-ATLASTREE-001' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://www.pathofexile.com/developer/docs/reference',
                'documentation_url' => 'https://www.pathofexile.com/developer/docs/reference',
                'terms_url' => 'https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy',
            ],
            'GGG-DOCUMENTED-API' => [
                'game_editions' => '["poe1","poe2"]',
                'reference_url' => 'https://www.pathofexile.com/developer/docs',
                'documentation_url' => 'https://www.pathofexile.com/developer/docs/reference',
                'terms_url' => 'https://www.pathofexile.com/developer/docs',
                'technical_access' => 'documented_oauth_api',
                'rate_limit_status' => 'documented_headers',
                'auth_requirements' => 'oauth_application',
                'data_quality_status' => 'official_structured',
                'patch_versioning_status' => 'operation_specific',
            ],
            'POEWIKI-CARGO-001' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://www.poewiki.net/',
                'documentation_url' => 'https://www.poewiki.net/wiki/Path_of_Exile_Wiki:Cargo',
                'terms_url' => 'https://www.poewiki.net/wiki/Path_of_Exile_Wiki:Copyrights',
                'redistribution_status' => 'unknown',
                'commercial_use_status' => 'unknown',
                'cache_storage_status' => 'prohibited',
                'technical_access' => 'cargo_api_candidate',
                'license_identifier' => 'NOASSERTION',
                'rate_limit_status' => 'requires_review',
                'auth_requirements' => 'none_observed',
                'data_quality_status' => 'community_structured',
                'patch_versioning_status' => 'page_revision',
                'update_frequency' => 'community_updated',
            ],
            'POENINJA-ECONOMY-001' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://poe.ninja',
                'documentation_url' => 'https://poe.ninja/docs/api',
                'terms_url' => 'https://poe.ninja/docs/api',
                'redistribution_status' => 'restricted',
                'commercial_use_status' => 'unknown',
                'cache_storage_status' => 'bounded',
                'technical_access' => 'documented_public_api',
                'license_identifier' => 'LicenseRef-poe-ninja-public-economy-api',
                'rate_limit_status' => 'documented_cache_headers',
                'auth_requirements' => 'none',
                'data_quality_status' => 'market_observation',
                'patch_versioning_status' => 'league_and_timestamp',
                'update_frequency' => 'provider_defined',
                'provenance_status' => 'approved_with_limits',
            ],
            'REPOE-CANDIDATE' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://github.com/brather1ng/RePoE',
                'documentation_url' => 'https://github.com/brather1ng/RePoE',
                'redistribution_status' => 'prohibited',
                'commercial_use_status' => 'prohibited',
                'cache_storage_status' => 'prohibited',
                'technical_access' => 'public_git_repository',
                'license_identifier' => 'NOASSERTION-GAME-DATA',
                'data_quality_status' => 'derived_client_data',
                'patch_versioning_status' => 'git_commit',
                'update_frequency' => 'upstream_commit',
                'provenance_status' => 'prohibited',
            ],
            'POB-GAME-DATA-CANDIDATE' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://github.com/PathOfBuildingCommunity/PathOfBuilding',
                'documentation_url' => 'https://github.com/PathOfBuildingCommunity/PathOfBuilding',
                'license_identifier' => 'MIT-software-NOASSERTION-embedded-data',
                'technical_access' => 'public_git_repository',
                'data_quality_status' => 'generated_and_embedded',
                'patch_versioning_status' => 'git_commit',
                'update_frequency' => 'upstream_commit',
            ],
            'POB2-GAME-DATA-CANDIDATE' => [
                'game_editions' => '["poe2"]',
                'reference_url' => 'https://github.com/PathOfBuildingCommunity/PathOfBuilding-PoE2',
                'documentation_url' => 'https://github.com/PathOfBuildingCommunity/PathOfBuilding-PoE2',
                'license_identifier' => 'MIT-software-NOASSERTION-embedded-data',
                'technical_access' => 'public_git_repository',
                'data_quality_status' => 'generated_and_embedded',
                'patch_versioning_status' => 'git_commit',
                'update_frequency' => 'upstream_commit',
            ],
            'DAT-SCHEMA-CANDIDATE' => [
                'game_editions' => '["poe1","poe2"]',
                'reference_url' => 'https://github.com/poe-tool-dev/dat-schema',
                'documentation_url' => 'https://github.com/poe-tool-dev/dat-schema',
                'license_identifier' => 'MIT',
                'technical_access' => 'public_git_repository',
                'redistribution_status' => 'allowed',
                'commercial_use_status' => 'allowed',
                'cache_storage_status' => 'bounded',
                'data_quality_status' => 'schema_only_no_game_rows',
                'patch_versioning_status' => 'git_commit',
                'update_frequency' => 'upstream_commit',
                'provenance_status' => 'approved_schema_only',
            ],
            'PYPOE-CANDIDATE' => [
                'game_editions' => '["poe1"]',
                'reference_url' => 'https://github.com/OmegaK2/PyPoE',
                'documentation_url' => 'https://github.com/OmegaK2/PyPoE',
                'license_identifier' => 'NOASSERTION-GAME-DATA',
                'technical_access' => 'client_file_extraction',
                'redistribution_status' => 'prohibited',
                'commercial_use_status' => 'prohibited',
                'cache_storage_status' => 'prohibited',
                'data_quality_status' => 'derived_client_data',
                'patch_versioning_status' => 'client_build',
                'provenance_status' => 'prohibited',
            ],
            'POE2-DATASET-CANDIDATE' => [
                'game_editions' => '["poe2"]',
                'documentation_url' => 'https://github.com/cryptopust/lootwright/blob/main/docs/compliance/source-register.md',
                'redistribution_status' => 'unknown',
                'commercial_use_status' => 'unknown',
                'cache_storage_status' => 'prohibited',
            ],
            'POE-DB-CANDIDATE' => [
                'game_editions' => '["poe1","poe2"]',
                'reference_url' => 'https://poedb.tw/',
                'documentation_url' => 'https://poedb.tw/',
                'technical_access' => 'public_website',
                'license_identifier' => 'NOASSERTION',
                'redistribution_status' => 'unknown',
                'commercial_use_status' => 'unknown',
                'cache_storage_status' => 'prohibited',
                'rate_limit_status' => 'unknown',
                'auth_requirements' => 'none_observed',
                'data_quality_status' => 'community_reference',
                'patch_versioning_status' => 'unknown',
                'update_frequency' => 'unknown',
                'provenance_status' => 'requires_review',
            ],
            'CRAFT-OF-EXILE-CANDIDATE' => [
                'game_editions' => '["poe1","poe2"]',
                'reference_url' => 'https://www.craftofexile.com/',
                'documentation_url' => 'https://www.craftofexile.com/',
                'technical_access' => 'public_website',
                'license_identifier' => 'NOASSERTION',
                'redistribution_status' => 'prohibited',
                'commercial_use_status' => 'prohibited',
                'cache_storage_status' => 'prohibited',
                'data_quality_status' => 'community_reference',
                'provenance_status' => 'prohibited',
            ],
            'POE-TRADE-VOCABULARY-CANDIDATE' => [
                'game_editions' => '["poe1","poe2"]',
                'reference_url' => 'https://www.pathofexile.com/developer/docs/reference',
                'documentation_url' => 'https://www.pathofexile.com/developer/docs/reference',
                'technical_access' => 'undocumented_internal_paths',
                'license_identifier' => 'NOASSERTION',
                'redistribution_status' => 'prohibited',
                'commercial_use_status' => 'prohibited',
                'cache_storage_status' => 'prohibited',
                'data_quality_status' => 'live_observation_not_canonical',
                'provenance_status' => 'prohibited',
            ],
            'USER-POB-001', 'USER-ITEM-TEXT-001' => [
                'game_editions' => '["poe1","poe2"]',
                'redistribution_status' => 'prohibited',
                'commercial_use_status' => 'prohibited',
                'cache_storage_status' => 'bounded',
            ],
            default => [],
        }];
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
