Lootwright

A traceable, AI-assisted build analysis and item-search planning tool for Path of Exile.

Türkçe README

Project status: pre-alpha / architecture and prototype stage.
The capabilities described below define the intended product. They should not be interpreted as a claim that a production service is already available.

Lootwright is an open-source web application designed to make Path of Exile build analysis and item searching easier to understand. A player supplies a Path of Building code, describes what they want to achieve, and receives deterministic findings, a prioritized upgrade plan, and a set of filters they can apply manually on the official Trade website.

The project is being designed for Path of Exile 1 first, with Path of Exile 2 supported through a separate adapter and ruleset after the PoE1 MVP is stable.

Lootwright does not interact with the game client and is not a trade bot, overlay, market indexer, or gameplay automation tool.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

The problem

Path of Exile offers an extraordinary amount of build freedom, but that freedom creates a difficult information problem:

Path of Building exposes a large amount of data without always explaining which problem should be fixed first.

The official Trade interface is powerful, but translating a build need into the correct filters requires substantial game knowledge.

Build guides often present a finished character without explaining which modifiers are mandatory, optional, replaceable, or budget-dependent.

Natural-language advice from an AI model can sound convincing while containing invalid modifiers, outdated rules, or recommendations for the wrong game version.

Lootwright aims to bridge this gap without replacing player judgment or automating the game.

What Lootwright is intended to do

A player will be able to:

Select Path of Exile 1 or Path of Exile 2.

Paste a PoB/PoB2 share code or item text they explicitly choose to provide.

Describe their character, target content, problems, play style, and budget in natural language.

Review the detected game edition, patch/ruleset compatibility, and unsupported data.

Receive deterministic build findings backed by versioned rules and source provenance.

See a prioritized upgrade plan rather than an unstructured list of possible changes.

Generate broad and strict manual Trade-filter recipes for relevant equipment slots.

Open the official Trade homepage and apply those filters manually.

Inspect why each recommendation was produced, which rule was used, and how confident the system is.

Example

I play a level 91 Scion built around armour and evasion.
My target is deep Delve, my boss damage is low, and I have a budget of 50 Divine Orbs.
I want to keep Mageblood and avoid rebuilding the entire character.

Combined with a user-supplied PoB code, Lootwright should be able to identify the relevant constraints, detect build conflicts or weak points, order the upgrades by dependency and expected impact, and create manual item-search recipes for the affected slots.

It must not invent a modifier, price, item, Trade identifier, or calculation simply because the request was written in natural language.

What Lootwright will not do

Lootwright is deliberately not designed to:

read or modify game memory, files, logs, screen contents, or network traffic;

inspect or control the Path of Exile client;

read the clipboard in the background;

automate keyboard input, mouse input, chat, whispers, invites, purchases, or party actions;

collect POESESSID, Path of Exile passwords, browser cookies, or session credentials;

scrape the official website, forums, Trade pages, or third-party build sites;

call or reverse-engineer undocumented GGG endpoints;

fetch, cache, monitor, rank, or republish live Trade listings;

generate encoded Trade searches from undocumented request formats;

act as a price-check overlay or trading bot;

promise that a build is optimal, immortal, or profitable;

allow donors or sponsors to influence recommendations.

How the system is designed

Lootwright separates game facts, deterministic analysis, and AI-generated language.

flowchart LR
    A[Player goal and budget] --> B[Intent extraction]
    C[User-supplied PoB or item text] --> D[Safe parser]
    B --> E[Canonical build intent]
    D --> F[Canonical build snapshot]
    G[Versioned PoE ruleset] --> H[Deterministic analysis engine]
    E --> H
    F --> H
    H --> I[Findings and upgrade priorities]
    I --> J[Manual Trade-filter recipes]
    I --> K[Optional AI explanation]
    J --> L[Player manually uses official Trade site]

Deterministic core

The analysis engine is the source of truth. The same build, ruleset, and engine version must produce the same normalized result. Every accepted finding and recommendation must contain:

the affected skill, item, passive, or build property;

deterministic evidence;

the rule and ruleset version used;

source provenance;

confidence and unsupported-data indicators;

an explanation trace linking the problem to the recommendation.

Path of Exile 1 and Path of Exile 2 use separate adapters and rulesets. A PoE1 identifier or assumption must never leak into a PoE2 analysis.

Limited AI role

AI is optional. The planned OpenAI integration uses the Responses API and Structured Outputs for two bounded tasks:

converting the player's natural-language request into a typed intent candidate;

explaining recommendations that were already produced by the deterministic engine.

AI cannot create canonical game data, approve an integration, override a policy decision, or add a recommendation that is absent from the deterministic result. The application must remain usable when AI is disabled or unavailable.

Manual Trade recipes

For each relevant item slot, Lootwright can prepare a recipe containing:

required modifiers and minimum values;

weighted optional modifiers;

incompatible or excluded modifiers;

base, rarity, influence, corruption, and other applicable constraints;

a broad search and a stricter alternative;

dependencies on other equipment slots;

the reason and provenance behind each filter.

The player remains responsible for entering the filters, reviewing listings, contacting sellers, and completing every action manually.

Data and integration policy

Every external source and capability is evaluated by a deny-by-default Policy and Provenance Gate.

An integration remains disabled when its permission, storage, redistribution, or commercial-use status is missing, expired, conflicting, or unclear. This includes community datasets and public websites whose technical accessibility does not itself grant permission for reuse.

The initial product path therefore relies on:

PoB/PoB2 codes and item text explicitly supplied by the player;

local, versioned ruleset imports with recorded source, checksum, parser version, and permission evidence;

documented GGG APIs only when valid application access, scopes, credentials, and current policy evidence exist;

original Lootwright interface assets and test fixtures.

Undocumented Trade endpoints, remote pobb.in fetching, forum scraping, GGG artwork, and publisher-owned datasets are not enabled by default.

Privacy and security principles

Collect only the data required for an analysis.

Treat PoB XML, notes, item text, and character names as hostile input.

Disable external XML entities, DTD loading, and unbounded decompression.

Never log complete private build codes or API secrets by default.

Do not send arbitrary PoB notes or unnecessary personal data to an AI provider.

Provide retention controls, portable export, and deletion.

Restrict outbound network access and require explicit allowlisting.

Apply per-user, per-IP, daily, and global AI usage limits.

Keep AI and every external integration behind independent emergency kill switches.

Technology and architecture

The planned implementation is a pragmatic modular monolith:

Area

Technology / approach

Backend

Laravel 13, PHP

Frontend

Inertia 3, Vue 3, TypeScript

UI

Tailwind CSS, shadcn-vue, original Lootwright design system

Database

PostgreSQL

Cache and queues

Redis, Laravel Horizon

Architecture

Domain-driven modular monolith

AI

Provider-neutral gateway; optional OpenAI Responses API adapter

AI output

Strict Structured Outputs with deterministic validation

Build input

Safely decoded and normalized PoB/PoB2 data supplied by the user

Trade output

Manual filter recipes; no live listing retrieval

The domain and analysis engine must not depend on Laravel, Eloquent, HTTP, the UI, or a specific AI provider.

Delivery plan

Phase 1 — PoE1 foundation

project constitution, threat model, and source registry;

Laravel application foundation;

safe PoB importer;

versioned PoE1 ruleset ingestion;

deterministic analysis engine;

manual Trade-filter recipes;

Turkish and English interface foundations.

Phase 2 — Useful PoE1 MVP

prioritized build findings and upgrade plans;

explainable content-goal checks for mapping, bossing, Delve, Simulacrum, Sanctum, and progression;

optional, budget-controlled AI intent and explanations;

privacy, deletion, export, evals, security hardening, and production operations.

Phase 3 — PoE2 adapter

separate PoB2 importer compatibility;

independent PoE2 ruleset and analysis rules;

edition-specific tests preventing PoE1 assumptions from leaking into PoE2;

PoE2 manual Trade-filter recipes when the required vocabulary has approved provenance.

Future integrations

A new integration is considered only if it is documented, permission evidence is recorded, and the Policy Gate allows the exact capabilities required. Technical feasibility alone is not sufficient.

Current status

Lootwright is currently in the architecture and prototype stage.

Product problem and boundaries defined

PoE-only scope selected

PoE1-first delivery plan selected

Deterministic-core and limited-AI architecture defined

GGG integration restrictions documented

Codex CLI implementation sequence prepared

Application scaffold

Safe PoB parser

Versioned ruleset ingestion

Deterministic analysis MVP

Manual Trade recipes

Public pre-alpha

The implementation plan is available in lootwright-poe-codex-cli-prompts.md.

Funding and sponsorship

Lootwright is intended to remain open source and accessible without creating paid gameplay advantages.

Voluntary funding may eventually be used to cover hosting, security, observability, and AI costs. Funding is disabled by default until the relevant policy and legal questions have been reviewed. If enabled later:

donations will not unlock features or AI quota;

recommendations will not be influenced by donors or sponsors;

there will be no paid ranking or sponsored item placement;

operating costs and material sponsorships will be disclosed;

the project will not claim endorsement by GGG or OpenAI.

The architecture also includes strict token budgets, caching, deterministic fallbacks, and AI-off operation so the project does not depend on unlimited API funding.

Contributing

Contribution guidelines, the code of conduct, security reporting instructions, and the final source-code license will be added before the first public development release.

Until then, proposed changes should preserve these non-negotiable principles:

No game-client interaction or gameplay automation.

No undocumented GGG endpoints or scraping.

No untraceable recommendation or accepted AI hallucination.

No cross-edition PoE1/PoE2 data leakage.

No monetization-based feature advantage.

No third-party data without recorded permission and provenance.

Legal and trademark notice

Lootwright is an independent community project. It is not developed, authorized, sponsored, or endorsed by Grinding Gear Games.

Path of Exile and Path of Exile 2, including their names, game data, artwork, characters, items, and related intellectual property, belong to Grinding Gear Games and their respective rights holders. The Lootwright source-code license will apply only to original Lootwright code and assets; it will not grant rights to third-party or publisher-owned material.

The project must follow the current Path of Exile Developer Docs and Terms of Use. A capability may be changed or disabled if policies or permissions change.

OpenAI is an optional technology provider, not a project sponsor or endorser. The planned integration follows the official Responses API and Structured Outputs documentation.
