# Lootwright: OpenAI Consideration One-Pager

Status: draft request package, not submitted and not evidence of eligibility,
funding, credits, sponsorship, or endorsement. Prepared 2026-08-15.

## Project

Lootwright is an MIT-licensed, open-source Path of Exile build-analysis project
intended to make evidence-backed build guidance accessible in Turkish and
English. Its product core is deterministic: parsers, immutable rulesets,
findings, prioritization, and manual item-search recipes produce traceable
results without an AI provider.

AI is an optional language boundary. It may convert a player's natural-language
goal into a closed `BuildIntent` candidate, ask a concise clarification, or
explain recommendations that the deterministic engine already produced. It may
not calculate the build, invent game identifiers, create recommendations,
change rankings, override policy, or access live market data.

## Request

We would like OpenAI to advise whether Lootwright may be considered for an
appropriate open-source or public-benefit support path and, if so, whether a
small, time-limited, budget-capped API credit allocation for reproducible evals
and opt-in language-quality testing is available.

This is a request for consideration only. The official [Codex for Open Source
Program Terms](https://learn.chatgpt.com/docs/codex-for-oss-terms) say that
selection is discretionary, submission does not guarantee selection or credits,
and API credits may require separate review. The public [Codex for Open Source
page](https://developers.openai.com/community/codex-for-oss) describes
maintainer-oriented benefits; Lootwright does not assume that product inference
or this project qualifies.

## Safety and accountability

- Responses API requests use strict Structured Outputs, no tools, bounded token
  ceilings, `store: false`, minimum redacted context, timeouts, and bounded
  transient-only retries.
- Every referenced term is resolved against the selected edition and immutable
  ruleset. Unknown terms fail validation and trigger at most one bounded repair.
- Prompt injection, provider refusal, timeout, schema failure, unknown IDs, or
  budget denial falls back to deterministic forms.
- Per-user, per-IP, daily, global, and monthly cost limits are enforced locally;
  AI is disabled by default and a separate egress switch defaults off.
- Reproducible fake-transport evals prove that AI wording cannot alter
  deterministic recommendations.

## Public benefit and access

The intended benefit is clearer, safer build education for a bilingual player
community without pay-to-win product mechanics. Funding or sponsorship can
never change features, quotas, accuracy, priority, rankings, support time, or
community visibility. Lootwright remains useful when AI is unavailable.

The project makes no claim of legal approval, GGG approval, OpenAI sponsorship,
free credits, program eligibility, or future selection.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
