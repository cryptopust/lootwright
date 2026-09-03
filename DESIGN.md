# Design System: Lootwright

Status: implemented visual foundation with fixture-backed product screens. It
does not imply that production analysis or end-user MVP flows are available.

## Overview

**Creative North Star: "The Forge Ledger"**

Lootwright is a dark technical atelier for deliberate build decisions. Obsidian
creates the low-glare workbench, Bone keeps long evidence readable, Ember marks
the next human action, and Arcane marks proof, provenance, and confirmed state.
The product uses familiar navigation and controls, then earns its identity
through ledger-like evidence rows, clear version stamps, and the original
geometric forge mark.

The system rejects generic AI tool marketing, official game UI imitation,
copied fantasy ornament, and price-overlay density. Product screens are compact
and predictable. Marketing surfaces may use the serif display voice, but task
labels, forms, data, and navigation remain quiet system sans and mono.

**Key Characteristics:**

- Evidence-forward hierarchy with visible source chains.
- Restrained full palette with fixed semantic roles.
- Flat, bordered surfaces and minimal state-driven motion.
- Edition identity repeated at every consequential boundary.
- Mobile layouts that preserve meaning rather than merely stack decoration.

## ARPG design-system extension

The application surfaces use a single dark, low-glare ARPG workbench theme.
These tokens are the source of truth for all new components; component styles
must reference semantic variables rather than literal colors. Values are OKLCH
so contrast can be reviewed without a hidden hex palette.

### Semantic tokens

| Token | Value | Meaning |
| --- | --- | --- |
| `--color-background` | `oklch(0.168 0.021 259)` | page field |
| `--color-surface` | `oklch(0.205 0.021 259)` | primary panel |
| `--color-surface-raised` | `oklch(0.242 0.022 259)` | nested work surface |
| `--color-grid-line` | `oklch(0.26 0.018 259)` | 32px grid |
| `--color-foreground` | `oklch(0.945 0.012 85)` | readable text |
| `--color-muted-foreground` | `oklch(0.68 0.021 259)` | secondary text |
| `--color-border` | `oklch(0.29 0.019 259)` | hairline |
| `--color-border-strong` | `oklch(0.38 0.02 259)` | selected control |
| `--color-primary` | `oklch(0.664 0.155 42)` | Ember action |
| `--color-accent` | `oklch(0.762 0.093 193)` | Arcane evidence |

Rarity roles are `--rarity-normal`, `--rarity-magic`, `--rarity-rare`,
`--rarity-unique`, `--rarity-currency`, and `--rarity-corrupted`; each has a
matching border token. `--state-verified`, `--state-unknown`,
`--state-warning`, and `--state-blocked` are reserved for status semantics.

### Evidence components

`ItemCard`, `AffixRow`, `RarityBadge`, `StatChip`, `UnknownValue`,
`EvidenceCallout`, `FindingRow`, `UpgradeRow`, `TerminalBlock`, and `ScopePanel`
are data-first components. Every number uses the mono family, every unknown is
explicitly rendered as `bilinmiyor`, and every progress bar has a text-bearing
`role="img"` label. A rarity or severity is never communicated by color alone.

The style-guide route is a fixture-only gallery. It contains no live market
claims, copied GGG assets, Trade API links, or external requests.

## Colors

The palette behaves like workshop materials: dark iron, paper, heat, and a
cool verification glow.

### Primary

- **Ember:** Primary actions, urgent but non-alarm findings, and current steps.

### Secondary

- **Arcane:** Evidence, confidence, provenance, focus, and confirmed status.

### Neutral

- **Obsidian:** Page and control background.
- **Bone:** Primary text and high-contrast control content.
- **Iron:** Raised work surfaces and selected navigation.
- **Ash:** Secondary text and quiet metadata.

**The Fixed Role Rule.** Ember never means evidence; Arcane never means a
commercial action. Color meaning remains stable across every page.

## Typography

**Display Font:** Newsreader Variable, locally bundled, with Georgia fallback
**Body Font:** DM Sans Variable, locally bundled, with system fallback
**Label/Mono Font:** JetBrains Mono Variable, locally bundled, with a generic
monospace fallback

**Character:** Editorial confidence on brand surfaces, native clarity inside
the application, and machine-readable precision for versions and evidence IDs.

### Hierarchy

- **Display** (500, 4.5rem, 0.95): Landing hero and rare brand statements.
- **Headline** (500, 2rem, 1.1): Page titles and decisive result headings.
- **Title** (500, 1.125rem, 1.3): Sections and finding groups.
- **Body** (400, 1rem, 1.6): Explanations capped near 68 characters.
- **Kicker** (mono, 0.6875rem, 0.18em): Versions, filters, and statuses.

**The Calculation Voice Rule.** Mono labels identify data and provenance. They
never turn paragraphs into terminal output or imitate a game overlay.

## Elevation

The system is flat by default. Depth comes from tonal layers, one-pixel borders,
and rare compact shadows on floating mobile navigation or focused popovers.
Shadows never decorate static result sections.

**The Workbench Rule.** If a surface can be separated by spacing or tone, it
does not receive a shadow.

## Components

### Buttons

- **Shape:** Compact crafted corners (2px).
- **Primary:** Ember on Obsidian, reserved for the next explicit user action.
- **Hover / Focus:** Small tone change, two-pixel Ember focus ring, no layout
  animation.
- **Secondary / Ghost:** Iron or transparent surfaces with Bone text and a
  complete one-pixel border where containment matters.

### Chips

- **Style:** Two-pixel corners with a one-pixel border; pills are not used.
- **State:** Text and symbols always repeat the color meaning.

### Cards / Containers

- **Corner Style:** Restrained two-pixel surface corners.
- **Background:** Obsidian or Iron based on hierarchy.
- **Shadow Strategy:** Flat by default.
- **Border:** Complete one-pixel low-contrast border. Only `ItemCard` rarity and
  `EvidenceCallout` evidence roles may add the specified two-pixel left stripe.
- **Internal Padding:** 16px on mobile and 20px on desktop.

### Inputs / Fields

- **Style:** Obsidian fill, complete Iron border, 2px corners, explicit labels.
- **Focus:** Ember outline with sufficient offset.
- **Error / Disabled:** Text explanation plus icon or label, never color alone.

### Navigation

Top-level navigation uses readable sentence case. The active section receives
an Iron tonal surface and `aria-current`; mobile navigation collapses into a
standard disclosure button with a text label.

### Evidence Chain

Findings and recommendations expose a consistent sequence: claim, why,
deterministic trace, ruleset/source, confidence, and limitation. Disclosure
buttons remain keyboard-native and never hide the primary limitation.

## Do's and Don'ts

### Do:

- **Do** use the exact Obsidian, Bone, Ember, and Arcane brand roles.
- **Do** repeat PoE1 or PoE2 in text at every import and recommendation boundary.
- **Do** label AI wording while stating that calculations remain deterministic.
- **Do** provide loading, empty, partial, stale, disabled, denied, and error
  states with useful recovery guidance.
- **Do** preserve exact manual recipe labels and decimal strings.

### Don't:

- **Don't** use generic AI tool marketing, chat-first layouts, or glowing purple gradients.
- **Don't** imitate official Path of Exile or Trade UI, or use copied fantasy ornament.
- **Don't** use GGG or PoE logos, item art, passive-tree imagery, screenshots, or copyrighted fonts.
- **Don't** build price-check overlay density, urgency counters, ads, sponsored ranking, paywalls, or donation pressure.
- **Don't** use decorative colored side stripes outside the approved rarity and
  evidence components, gradient text, decorative glassmorphism, nested cards,
  or unexplained scores.
- **Don't** present estimates as facts or hide evidence behind AI wording.
