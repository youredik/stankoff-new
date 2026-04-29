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
| ~~**S2**~~ | ~~Body-vs-signature mismatch is not detected.~~ **Retracted on 2026-04-29** — the Stankoff team reproduced the test on their side and on ours (signed body A, sent body B → 401 INVALID_SIGNATURE). The original 202 was caused by a bug in our stress harness: `json.dumps()` defaults to space-separated separators (`"description": "x"`), so our `replace(b'"description":"x"')` call did not match anything and `body_signed == body_sent`. **Their HMAC verifier is correct.** See "Erratum" section below. | **N/A** |
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

### ~~S2 — signature scope~~ — **Retracted (false positive)**

The original suspicion that Stankoff did not bind the signature to the body
bytes was **wrong**. After their team raised it, we re-tested with a
corrected harness against both `fake-stankoff` (a known-correct verifier)
and real preprod:

```python
body_A = json.dumps({...})                              # bytes A
body_B = body_A.replace(b'"x": "old"', b'"x": "new"')   # bytes B (proper match
                                                        #  — note the space!)
sig    = HMAC(timestamp + "." + body_A, secret)         # signature for A
POST inbox with sig in header, body_B as body

Result: 401 INVALID_SIGNATURE   ← both fake and preprod
```

The earlier 202 came from the harness bug: `json.dumps()` in our stress
script used default separators (`(', ', ': ')` — note the spaces), so
the `replace()` call's needle (`b'"description":"x"'`) did not match the
haystack (`b'"description": "x"'`) and `body_A == body_B` — the signature
was correct for the body actually sent.

**Lesson learned:** every assert in a security-relevant test must include
a byte-level differentiation check (e.g. `assert sha256(body_A) != sha256(body_B)`).
We added one to the recheck script (`/tmp/s2_recheck.py`) and to this
document so future testers don't repeat the same mistake.

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

## What we asked the Stankoff team — and their response (2026-04-29)

In order of priority (shared with them after the initial run):

1. **S1**: introduce a queue / rate-limit on the inbox endpoint so high
   concurrency is shed gracefully (429 with `Retry-After`) instead of 500.
   → **Confirmed by their team.** Root cause: static `producer_id` for YDB
   Topics fences losing sessions when concurrent writers race (per
   ydb.tech/docs/concepts/topic + Kafka KIP-447 EOS pattern). Fix in
   progress: per-request unique `producer_id`. Phase 2 will add explicit
   rate-limit (429 + `Retry-After`).
2. ~~**S2**~~: **retracted by us** — see "Erratum" above. Our test was
   buggy; their verifier is correct.
3. **S3**: consider a webhook-callback mechanism for asynchronous
   validation failures, or expose a `GET /inbox/events/{eventId}` so we
   can poll.
   → **Documented as by-design** (transactional outbox: 202 = "queued for
   processing", not "stored"). They will add a pull endpoint in Phase 1.5:
   `GET /api/v1/integrations/dedupe/{integrationId}/{idempotencyKey}` —
   returns `resultStatus: succeeded | failed | dlq` plus an `errorMessage`
   for failed/DLQ rows. **We will integrate this** — see "Future work" below.
4. **S4**: malformed JSON should be 400, not 500.
   → **Confirmed and fixed by their team** (catch JSON parse → 400
   VALIDATION_ERROR). Our retry classifier will no longer loop on garbage
   payload.

## Erratum — S2 was a false positive

The original report flagged that Stankoff did not validate the
body-vs-signature relationship. **This was wrong.** The Stankoff team
reproduced the scenario both on their side and ours — both versions
return 401 INVALID_SIGNATURE for body/signature mismatch, as expected.

The cause was a `json.dumps()` defaults issue in our Python stress
harness: by default the JSON output uses `(', ', ': ')` as separators
(with spaces), so the byte-level `replace()` call we used to mutate the
body never matched anything, leaving body_signed and body_sent identical.
We caught this by re-running the test with an explicit
`assert sha256(body_A) != sha256(body_B)` — which immediately failed,
pointing at the real bug.

**Action taken on our side:**
- Updated `/tmp/s2_recheck.py` with the byte-level differentiation
  assertion.
- Verified against fake-stankoff (known-correct verifier): 401.
- Verified against real preprod: 401.
- Retracted the finding in this document.

This entry stays in the document deliberately — false positives are part
of the audit trail, and future testers benefit from understanding what
went wrong and how it was caught.

## What changed on our code side after this round

Driven by their planned Phase 1.5 / Phase 2 features, we updated our
client to be ready before they ship:

- **`ErrorClassifier` now classifies 408 and 429 as Transient**
  (previously they would have fallen through to the "default 4xx
  → permanent" branch). 408 is RFC-correct; 429 is needed for the
  Phase 2 rate limiter so a rate-limited request is retried with
  exp-backoff instead of being permanently failed. Tests added.
- **Retry-After header parsing** — not implemented yet; Symfony
  Messenger uses its own retry strategy. Once Stankoff begins emitting
  Retry-After we will likely add a `DelayStamp` to the redelivered
  message to honour it. Tracked under "Future work".

## Future work, queued for when Stankoff ships their Phase 1.5/2

- **Async failure inspection via `/pull/dedupe`.** When Phase 1.5 lands,
  add a periodic (cron) consumer that scans `integration_outbox_event`
  rows in `succeeded` status older than N minutes and fetches their
  Stankoff dedupe-row status. If `resultStatus = failed` or `dlq`, mark
  our outbox row as `permanently_failed` with the upstream error message.
  This closes the lazy-ingest gap (S3): a 202 → "queued" only confirms
  reception; the pull-API confirms downstream processing.
- **Files API stress.** Their team noted they did not stress the Files
  API. Once their producer_id fix lands, run a 5×mp4-attachments + webhook
  scenario end-to-end against preprod.
- **MIME allowlist alignment.** Current code does not pre-validate file
  MIME types client-side — we rely on Stankoff's server-side allowlist
  (16 types as of Q3.1). Adding a client-side guard is cheap and saves
  a network round-trip on bad files; track if/when needed.
- **Retry-After honour.** If 429 begins arriving with `Retry-After`,
  parse it and emit `DelayStamp` for the next retry instead of using
  the default exp-backoff schedule.

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
