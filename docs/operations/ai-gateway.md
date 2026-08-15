# AI Gateway Operations

Status: adapter implemented and tested; production execution remains blocked by Policy Gate review.

## Runtime boundary

The provider-neutral orchestrator lives under `src/Application/AIGateway`; it imports no Laravel, HTTP, storage, or provider SDK type. Laravel infrastructure under `app/Modules/AI` owns the OpenAI Responses API HTTP adapter, policy authorization, PostgreSQL budget reservations and audits, and Redis-compatible cache adapter.

The deterministic intent parser runs before every provider decision. A provider call then requires all of the following:

- explicit user opt-in;
- `OPENAI_ENABLED=true` and a configured secret;
- an exact Policy Gate `allow` for `openai.responses.intent` or `openai.responses.explanation`;
- request token ceilings and all user, IP, daily, global, and monthly budgets;
- minimum structured context and a strict local output validation pass.

The seeded decision remains `require_review`, which is non-executable. Enabling the environment flag alone cannot bypass it.

## Request and response handling

The adapter uses the official [Responses API](https://developers.openai.com/api/reference/resources/responses/methods/create) and [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs) shape: `text.format.type=json_schema`, `strict=true`, and `additionalProperties=false`. Every request sets `store=false`, `truncation=disabled`, a hard `max_output_tokens`, a hashed `safety_identifier`, and an opaque `prompt_cache_key`. It supplies no tools, files, web search, functions, previous response, or conversation.

The application locally decodes and validates every response, checks every referenced term against the exact edition/patch ruleset vocabulary, and verifies explanation codes exactly match the deterministic findings and recommendations in their original order. Refusals, unknown terms, unsafe prose, timeouts, exhausted budgets, policy denials, and invalid schemas produce typed deterministic fallback content. Malformed or schema-invalid output permits at most one fresh repair attempt. Refusal and policy/validation failures are terminal.

Temporary connection failures and temporary `429`, `500`, `502`, `503`, or `504` responses use bounded exponential backoff with jitter and honor `Retry-After`. Billing and quota codes such as `project_spend_limit_exceeded`, `organization_spend_limit_exceeded`, `organization_usage_limit_exceeded`, and `credit_balance_exhausted` are never retried. This follows the official [rate-limit](https://developers.openai.com/api/docs/guides/rate-limits), [error](https://developers.openai.com/api/docs/guides/error-codes), and [spend-limit](https://developers.openai.com/api/docs/guides/spend-limits) guidance.

## Model and HTTP client decision

`gpt-5.4-nano` remains the configurable MVP default because the official [model page](https://developers.openai.com/api/docs/models/gpt-5.4-nano) currently lists Responses API and Structured Outputs support and describes it as the lowest-cost GPT-5.4-class extraction model. The model is configuration, never a canonical identifier source.

The official [SDK page](https://developers.openai.com/api/docs/libraries) permits a preferred HTTP client and does not list an official PHP SDK. Lootwright therefore uses Laravel's maintained HTTP client and adds no provider dependency. Revisit this decision if OpenAI publishes and maintains an official PHP SDK with a material security or schema advantage.

## Manual smoke test

Normal tests and CI always use fake transports. A maintainer may deliberately run one synthetic request only after Policy Gate activation:

```powershell
php artisan ai:smoke-openai --confirm --max-cost-micro-usd=1000
```

The command refuses without confirmation, a positive cap, configuration, secret, Policy Gate allow, and available local budgets. It sends no user or PoB data, makes at most one request, and prints only provider/model, validation status, token usage, and micro-USD cost. It never prints the secret or raw response.

Incident response: set `OPENAI_ENABLED=false`; if broader containment is needed, activate the policy global or source/capability kill switch. Rotate a suspected API key in the OpenAI project and deployment secret store. Do not paste provider request bodies into tickets or logs.
