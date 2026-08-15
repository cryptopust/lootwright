# Token Cost Model

Status: configuration-driven planning estimate, reviewed 2026-08-15. Values are
not bills, price guarantees, credits, or sponsorship commitments.

## Pricing source

The model assumption is `gpt-5.4-nano` using standard processing. The official
[OpenAI API pricing documentation](https://developers.openai.com/api/docs/pricing),
retrieved 2026-08-15, listed these prices per one million tokens:

| Category | USD per 1M | Configuration value in micro-USD per 1M |
| --- | ---: | ---: |
| Uncached input | $0.20 | 200,000 |
| Cached input | $0.02 | 20,000 |
| Output | $1.25 | 1,250,000 |

Model prices and regional uplifts can change. Operations must re-read the
official page before changing the model, processing tier, or region. No free
credit, discount, sponsorship, or program eligibility is assumed.

## Formula

For each monthly scenario:

- `A` = analyses per month;
- `R` = AI opt-in/eligible rate in basis points;
- `C` = average calls per AI-enabled analysis;
- `Iu`, `Ic`, `O` = uncached input, cached input, and output tokens per call;
- `Pu`, `Pc`, `Po` = corresponding micro-USD prices per million tokens.

```text
AI calls = ceil(A * R / 10,000) * C

AI micro-USD =
  ceil(AI calls * Iu * Pu / 1,000,000)
  + ceil(AI calls * Ic * Pc / 1,000,000)
  + ceil(AI calls * O * Po / 1,000,000)

AI cents = ceil(AI micro-USD / 10,000)
Projected monthly cents = configured hosting cents + AI cents
```

All application accounting uses non-negative integers and rounds upward. The
same assumptions live in `config/funding.php`; token prices come from
`config/ai.php`. Operating projections contain no player, build, account, or
supporter data.

## Scenarios

Configured hosting is $35 compute/database/cache, $10 encrypted backups, and
$10 observability/domain operations per month. These are planning assumptions,
not vendor quotes.

| Scenario | Analyses/month | AI rate | Calls per eligible analysis | Tokens per call (uncached/cached/output) | Hosting | AI estimate | Total estimate |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Low | 1,000 | 25% | 1 | 700 / 200 / 180 | $55.00 | $0.10 | $55.10 |
| Base | 10,000 | 40% | 1 | 900 / 300 / 240 | $55.00 | $1.95 | $56.95 |
| High | 50,000 | 60% | 2 | 1,100 / 400 / 320 | $55.00 | $37.68 | $92.68 |

These estimates deliberately exclude payment processing because no payment
provider is integrated. They also exclude taxes, legal/accounting work, human
support, unexpected traffic, regional uplift, and provider price changes.

## Cost controls

The reduction order is deterministic parsing, privacy-permitted cache, minimum
context, output cap, then provider call. Per-request ceilings, per-user/per-IP
daily budgets, global daily budget, and a hard local monthly breaker apply.
Provider failure or budget denial selects a local deterministic template.
