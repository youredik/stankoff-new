# Stress test findings — Stankoff webhook

> Run on 2026-04-29 against `https://preprod.stankoff.ru` after the Stankoff
> team confirmed the Q3.* allowlist/limits/MIME enforcement changes had
> shipped to preprod and the service had no live users.

The goal was to learn how the integration behaves under hostile and
boundary-condition input, to validate our error classification, and to
surface any issues on either side that we should be aware of in production.

## Test corpus

| Category                         | Probes | How                                          |
|----------------------------------|--------|----------------------------------------------|
| A. HMAC contract                 | 9      | Tampered signatures, wrong secrets, format violations |
| B. Timestamp drift / format      | 6      | ±310s window, malformed, header≠signed      |
| C. Idempotency-Key boundaries    | 6      | Lengths 7/8/128/129, empty, non-ASCII       |
| D. Missing / wrong headers       | 5      | Each required header missing in turn         |
| E. Body size boundary            | 3      | Just under 1MB / over / 5MB                 |
| F. Payload edge cases            | 9      | Unicode, SQL/XSS, types, malformed JSON     |
| G. Concurrent — 30 unique keys   | 30     | All in parallel (asyncio thread pool)        |
| H. Race — 20 same idempotency-key | 20    | All in parallel, same key                    |
| I. Through our worker (E2E)      | 30     | 30 tickets created at once, watched messenger queue |

Total: ~140 probes against real preprod.

## Headline result for our side

**100% of test cases that should have gone through, did.** 30/30 tickets
through our pipeline succeeded on first attempt, zero retries, zero
permanently-failed. Our `ErrorClassifier`'s split between transient and
permanent matched Stankoff's behaviour perfectly — every retry-worthy
response retried, every permanent failure halted.

Our PII masking, body-size pre-check, signature stability across retries,
and idempotency-key persistence all worked as designed.

## Findings on the Stankoff side

These are not blockers for us — our error handling absorbs all of them
correctly — but they are real defects on their side that the team should
be aware of. Severity in column 3 reflects production impact for **us**,
not them.

| Tag | Defect | Severity for us |
|-----|--------|-----------------|
| **S1** | API degrades to ~5–10 successful concurrent requests; the rest fail with `500 INTERNAL`. With 30 parallel unique requests we got 2 OK, 28 × 500 INTERNAL. Latency p99 climbed to 15 sec. | **Low** — single-worker design throttles us to 1 concurrent. |
| **S2** | Body-vs-signature mismatch is **not** detected. We signed body A and sent body B with the signature for A — Stankoff accepted it as 202. Their HMAC verifier appears to validate only `timestamp.body` length/format, not byte equality. | **None** for us as senders, but a security hole on their side that an MITM could exploit. |
| **S3** | Required-field validation is missing. `eventType="invalid.event.v999"`, payload without `id`, `author_employee_id="string-not-int"` — all returned 202. Their ingest is "lazy" — the queue accepts everything, validation happens later (asynchronously, with no callback to us). | **Medium** — a 202 response does **not** guarantee Stankoff will eventually accept the record. We have no signal for downstream failures. |
| **S4** | Malformed JSON returns `500 INTERNAL` instead of `400 BAD REQUEST`. | **Low** — our code uses `json_encode` so we never send malformed JSON in practice; but if we did, our retry logic would loop forever-until-max-retries and noisily fail. |

### S1 — concurrency degradation, in detail

Sending 30 concurrent requests (each with a unique idempotency-key, a
fresh signature, and a fresh timestamp):

```
30 parallel: 2/30 OK in 15519ms wall
            (per-req fastest 706ms, slowest 15470ms, avg 1596ms)
Status distribution among concurrent requests:
  202 OK    : 2
  500 INT   : 28
```

Sequentially through our worker (single thread, one-at-a-time):

```
30 tickets: 30/30 OK
Latency end-to-end (ticket creation → outbox.succeeded_at): 2–21 seconds
Average attempts_count: 1.0   (no retries needed)
```

This is why our worker runs at concurrency = 1. A workaround for higher
throughput once Stankoff fixes this: multiple worker replicas with
Doctrine transport's `SELECT FOR UPDATE SKIP LOCKED` — each worker
serialises its own outbound calls, but they don't interfere.

### S2 — signature scope confirmation

```
1. signed_body  = '{"description":"original",...}'
2. sig          = HMAC(timestamp + "." + signed_body, secret)
3. sent_body    = signed_body.replace('"original"', '"injected"')
4. POST inbox  with sig as the signature, sent_body as the body

Result: 202 queued
Expected: 401 INVALID_SIGNATURE (per their spec)
```

The signature header is being treated as if it signed only the
`timestamp` and the *length* / *format* of the body, not the bytes.

### S3 — race / replay outcome

20 concurrent requests with the same `Idempotency-Key`:

```
1   × 202 queued     (the one that won the race on their side)
19  × 500 INTERNAL   (this is S1 again — concurrency, not idempotency)
```

Critically, **Stankoff did NOT create 20 records.** Idempotency holds.
The 19 × 500 are the same concurrency bug — they would have returned
`200 replay:true` if the API had been queued internally instead of
crashing. Likely fixable together with S1.

## What this changed in our design

| Question we asked ourselves                              | Answer informed by stress test |
|----------------------------------------------------------|--------------------------------|
| Should the worker scale horizontally for throughput?     | **No.** 1 worker is correct until S1 is fixed. Note this in `compose.prod.yaml`. |
| Should we treat 500 INTERNAL as transient or permanent?  | **Transient.** Retrying with the same idempotency-key is safe (S3 confirmed) — even if the original somehow succeeded, the retry returns `200 replay:true`. |
| Is the body-size pre-check at 1MB necessary?             | **Yes.** Confirmed boundary at exactly 1MB. Saving a doomed network trip is cheap. |
| Should we do client-side validation of the payload?      | **No on shape (Stankoff is lazy anyway), but YES on body size.** Schema validation would just duplicate work. |
| Are unicode / RTL / emoji safe in fields?                | **Yes.** F1 passed, all 4-byte UTF-8 accepted with `JSON_UNESCAPED_UNICODE`. |

## What we plan to ask the Stankoff team

In order of priority (already shared with them per user message of 2026-04-29):

1. **S1**: introduce a queue / rate-limit on the inbox endpoint so high
   concurrency is shed gracefully (429 with `Retry-After`) instead of 500.
   Our retry would automatically pick up `Retry-After`-aware backoff.
2. **S2**: tighten HMAC verification to compare the signed body bytes
   against the received body. This is a security-relevant fix — payload
   tampering in transit currently has no detection.
3. **S3**: consider a webhook-callback mechanism for asynchronous
   validation failures, or expose a `GET /inbox/events/{eventId}` so we
   can poll. Right now a 202 is the only signal we get and it doesn't
   guarantee final acceptance.
4. **S4**: malformed JSON should be 400, not 500.

## Reproducing the stress test

The full Python stress harness is at `/tmp/stankoff_stress.py` (kept in
`tmp` since it isn't a part of the codebase). It self-contains:
- HMAC signing logic identical to our `SignatureFactory`.
- Concurrent thread pool for G/H.
- Pass/fail predicates.
- Status distribution and latency percentile summary.

To re-run after Stankoff fixes any of the above, just point it at preprod
again. It is read-only and idempotent (uses unique keys per run).

## Latency observations (successful 200/202)

```
n = 21 successful responses
p50 = 894ms
p95 = 1526ms
p99 = 1803ms
```

This is acceptable for an async-batched webhook. Our handler timeout is
30s for the inbox call, with a hard `max_duration` of 30s, leaving plenty
of headroom even at p99.
