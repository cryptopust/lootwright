# AI Cost Controls

Status: MVP accounting baseline, reviewed against official pricing on 2026-08-15.

## Price table

The default `gpt-5.4-nano` standard rates in the official [OpenAI pricing documentation](https://developers.openai.com/api/docs/pricing) are recorded as integers per one million tokens:

| Token category | USD per 1M | Configured micro-USD per 1M |
| --- | ---: | ---: |
| Input | $0.20 | 200,000 |
| Cached input | $0.02 | 20,000 |
| Output | $1.25 | 1,250,000 |

Regional processing may add 10% according to the current model page. Regional processing is not enabled by this implementation. Pricing is operational configuration and must be reviewed before changing models or regions.

Cost is calculated with integer micro-USD arithmetic and rounded upward. Without a provider tokenizer dependency, preflight input accounting uses a deliberately conservative UTF-8 byte upper bound plus fixed request overhead and includes the strict schema. Before a network call, the gateway reserves the configured worst-case input/output cost, including capacity for the single permitted schema-repair attempt. After a response, it atomically settles actual reported usage and releases the remainder. A failed call releases its local reservation; provider-side usage dashboards remain the billing authority.

## Enforced limits

PostgreSQL counters enforce per-user daily, per-IP daily, global daily, and global monthly limits. The monthly limit is a hard local circuit breaker. Reservations and settlement are transactional so concurrent requests cannot all pass against the same remaining allowance. Zero or missing limits fail closed.

Deployment must also configure an OpenAI project hard spend limit; the official [spend-limit guide](https://developers.openai.com/api/docs/guides/spend-limits) warns that enforcement is not instantaneous, so the local breaker remains necessary. Spend alerts are monitoring only and do not replace a hard limit.

Cost-reduction order is fixed: deterministic intent parsing, privacy-permitted normalized cache, minimum structured context, bounded output, then provider call. AI is never called solely to reformat content that local Turkish or English templates can render.
