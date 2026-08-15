# Draft Application Email

Status: unsent draft. Replace bracketed operator facts only with verified
information before sending. Do not add confidential player or repository data.

## Subject

Request for consideration: bounded API eval support for open-source Lootwright

## Body

Hello OpenAI team,

I maintain Lootwright, an MIT-licensed open-source project for deterministic,
evidence-backed Path of Exile build analysis and bilingual Turkish/English
explanations. Repository: `[public repository URL after verification]`.

I am writing to ask whether Lootwright may be considered for an appropriate
open-source or public-benefit support path. If the project and proposed use are
eligible, we would value a small, time-limited, budget-capped API credit grant
for reproducible Structured Outputs evals and opt-in language-quality testing.
We understand that applying does not guarantee selection, sponsorship, credits,
or access, and that API credits may require separate review. If the Codex for
Open Source program is limited to maintainer workflows and does not cover this
use, guidance to the appropriate channel would be appreciated.

Lootwright's calculations are not performed by AI. A framework-independent,
versioned engine parses bounded user-submitted input, resolves an immutable
edition/ruleset, produces deterministic findings, ranks upgrades, and records
input/output hashes and evidence. Optional AI is limited to:

1. converting natural-language goals into a closed `BuildIntent` candidate;
2. asking a concise clarification when confidence is insufficient; and
3. explaining findings and recommendations already present in deterministic
   output.

The gateway uses the Responses API with strict JSON Schemas, extra-property and
enum rejection, term resolution, one bounded repair, token ceilings, minimized
redacted context, caching where privacy permits, `store: false`, no tools,
bounded retries, and local per-user/IP/global/monthly budgets. AI can neither
invent IDs nor change a recommendation. Disabled, refused, invalid, timed-out,
or budget-denied calls fall back to deterministic Turkish/English templates.

Our reproducible fake-transport evals cover malformed output, refusal, timeout,
rate limit, unknown IDs, prompt injection, schema failure, budget denial, and
snapshots proving AI cannot alter deterministic recommendations. No live key is
used in normal tests or CI.

Attached in this documentation package are the architecture, responsible-AI
and eval plan, configuration-driven token cost model, milestone plan, and
public-impact intent. We would report aggregate usage and eval outcomes without
raw player prompts or personal data.

Could you advise whether this project and narrowly bounded evaluation use are
eligible for consideration, and what verification or application material is
required? We will not describe a response as endorsement or activate provider
usage beyond the exact written scope.

Thank you,

`[verified maintainer name]`

`[project-role and non-confidential contact]`

---

OpenAI references reviewed 2026-08-15:

- [Codex for Open Source](https://developers.openai.com/community/codex-for-oss)
- [Codex for Open Source Program Terms](https://learn.chatgpt.com/docs/codex-for-oss-terms)
- [OpenAI API pricing](https://developers.openai.com/api/docs/pricing)

This draft makes no claim of OpenAI sponsorship, free credits, program
eligibility, selection, or endorsement.
