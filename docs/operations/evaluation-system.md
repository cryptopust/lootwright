# Reproducible Evaluation System

Status: fast and extended structural suites are implemented with original tiny
fixtures. Live-provider evaluation remains disabled by default and is never a
normal CI task.

## Purpose and truth boundary

The evaluation harness runs the real PoE1/PoE2 import coordinator, provider-neutral
AI Gateway validation with a fake transport, and the PoE1 Manual Trade Recipe
generator. Production game rulesets and the production deterministic game engine
remain unavailable and fail closed. The finding and recommendation cases are
therefore explicitly marked `fixture_structural`: they test evaluation structure,
trace enforcement, edition isolation, replay, and regression behavior, not real-game
finding accuracy.

The case envelope is versioned by
[`evaluation-case.schema.json`](../../evals/schema/evaluation-case.schema.json).
Committed fixtures are tiny, original, and contain no copied player builds. No suite
calls GGG, Trade, OpenAI, or another network endpoint during normal local or CI runs.

The design follows official OpenAI guidance on constructing evals: define repeatable
ground truth, objective success criteria, and regression comparisons before changing
the system. See [Constructing evals](https://developers.openai.com/tracks/ai-application-development#constructing-evals),
reviewed 2026-08-16. Lootwright does not use an AI judge for critical gates.

## Suites

| Suite | Invocation | Contents | Automation |
| --- | --- | --- | --- |
| Fast | `composer run eval:fast` | 31 parser, intent, ruleset, structural deterministic, and Trade cases | Required in normal CI |
| Extended | `composer run eval:extended` | Fast plus 50-run replays, deep XML, and decompression-bomb cases | Manual before ruleset/parser releases |
| Live OpenAI | `composer run eval:live -- --confirm --max-cost-micro-usd=CAP` | At most one bounded intent-schema request | Explicit manual operation only; never CI |

`eval:run --suite=fast` refuses private fixtures. An extended run may include
ignored private cases with `--include-private`; each case must declare
`user_authorized=true`. Private runs may never update a committed baseline.

## Metrics and critical gates

Rates use basis points, where `10000` means 100%. A denominator with no applicable
case is treated as satisfied, so reviewers must also inspect `case_count` and case
coverage when changing the suite.

| Metric | Fast/extended gate |
| --- | ---: |
| Parser success and safe-failure rates | 100% |
| Edition detection precision | 100% |
| Reviewed structural finding precision | 100% |
| Forbidden cross-edition recommendations | 0 |
| Unsupported-data disclosure | 100% |
| Recommendation and Trade trace completeness | 100% |
| Undocumented endpoint or network calls | 0 |
| AI schema validity and canonical-ID resolution | 100% |
| Accepted hallucinated canonical IDs | 0 |
| Deterministic replay equality | 100% |
| Per-case latency | at most 500 ms |
| Per-case memory delta | at most 16 MiB |
| Estimated fake-provider input/output | configured token ceilings |
| Estimated fake-provider cost | at most 2,000 micro-USD per call |

The network metric applies to the closed harness: all provider behavior is supplied
by in-process fakes and all evaluated parsers/generators are local. It is not a claim
about an uninstrumented production network. Policy and egress denial remain separate
integration gates.

## Reports and regressions

Each run writes machine-readable JSON and human-readable Markdown beneath
`storage/app/evaluations/`. Reports contain case IDs, statuses, metrics, hashes, and
regression paths. They contain no fixture bodies, prompts, notes, secrets, private
identifiers, or provider output. Private case IDs are replaced with a SHA-256-derived
reference.

Committed baselines under `evals/baselines/` contain stable case fingerprints and
non-timing metrics. Latency and memory remain enforced per run but are excluded from
byte-stable regression comparison. Extended baselines record only extended case
fingerprints; the combined fast-plus-extended source hash still detects changes to
either source document.

## Reviewing golden expectations

Do not update a baseline merely because a diff exists.

1. Run the relevant suite without `--update-baseline` and inspect every failed case,
   threshold violation, fingerprint diff, ruleset/parser/prompt version, and source
   hash.
2. Decide whether the code is wrong or the reviewed expectation is wrong. Fix code
   first when behavior violates policy, provenance, edition isolation, uncertainty,
   or trace requirements.
3. For an intentional semantic change, review the case input and expected structure
   side by side. Add a new boundary/negative case when coverage changed.
4. A public-fixture run must pass all thresholds before baseline update. Never update
   from a private run, a failed run, or live-provider output.
5. Record a named reviewer and a specific reason of at least 20 characters:

   ```powershell
   php artisan eval:run --suite=fast --update-baseline --reviewer=HANDLE --reason="Reviewed intentional parser contract change ..."
   ```

6. Review the baseline diff itself. It must contain no raw/private content, unexplained
   case removal, relaxed critical threshold, or cross-edition acceptance.

A behavioral change increments `suite_version`; an envelope change increments
`schema_version`. Ruleset, parser, source, and prompt-template versions remain
independent provenance dimensions and must not be hidden by a baseline refresh.

## Live-provider safety

`eval:live-openai` refuses execution unless all of these are true:

- the operator supplies `--confirm` and a positive `--max-cost-micro-usd`;
- `OPENAI_LIVE_EVALS_ENABLED=true` and `OPENAI_ENABLED=true`;
- an API key exists in the deployment secret store;
- the exact intent operation passes Policy Gate and local budget reservation;
- worst-case request plus one bounded repair fits below the operator cap;
- the process is not running in CI.

The default input is synthetic. An optional file must resolve below ignored
`evals/private/`, be at most 16 KiB, be explicitly authorized for both evaluation and
provider processing, and use `--allow-private`. Email, IP, API-key, and bearer-token
patterns are redacted before sending. Only hashed reference, provider/model, schema
outcome, token use, latency, and cost are stored. Raw request and response content are
not reported. Provider access, eligibility, pricing, or credits are never assumed.

