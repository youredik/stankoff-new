# Architecture — Stankoff webhook integration

> Status: implemented and tested as of 2026-04-29.
> Authors: stankoff-new team.

This document explains **how** the integration is built and **why** each
piece exists. If you are about to change the integration, read this first.

## The shape of the system

```
                        ┌─────────────────────────────────────────────┐
                        │ POST /support_tickets  (HTTP / GraphQL)     │
                        │ OIDC-protected, original API contract       │
                        └──────────────────────┬──────────────────────┘
                                               │
                        ┌──────────────────────▼──────────────────────┐
                        │ SupportTicketCreateProcessor                │
                        │  1. persist + flush ticket                  │
                        │  2. if STANKOFF_INTEGRATION_ENABLED:        │
                        │       try {                                 │
                        │         create IntegrationOutboxEvent row   │
                        │         flush                               │
                        │         bus->dispatch(ForwardSupportTicket  │
                        │                       Created($outboxId))   │
                        │       } catch (\Throwable $e) {             │
                        │         logger->critical(...)               │
                        │       }                                     │
                        └──────────────────────┬──────────────────────┘
                                               │
                        ┌──────────────────────▼──────────────────────┐
                        │ Doctrine Messenger transport                │
                        │   table:    messenger_messages              │
                        │   trigger:  pg_notify('messenger_messages') │
                        │   queue:    stankoff_integration            │
                        │   retry:    5 × exp backoff (5s→60s) jitter │
                        │   failure:  separate `failed` queue         │
                        └──────────────────────┬──────────────────────┘
                                               │
                        ┌──────────────────────▼──────────────────────┐
                        │ php-worker container (separate process)     │
                        │   command: messenger:consume                │
                        │            stankoff_integration             │
                        │            --time-limit=3600                │
                        │            --memory-limit=256M              │
                        └──────────────────────┬──────────────────────┘
                                               │
                        ┌──────────────────────▼──────────────────────┐
                        │ ForwardSupportTicketCreatedHandler          │
                        │   1. load outbox; idempotent guard          │
                        │   2. mark in_progress, attempts++           │
                        │   3. if files enabled and !already done:    │
                        │        FilesUploader.uploadAll              │
                        │   4. PayloadBuilder.build(ticket, fileIds)  │
                        │   5. SHADOW_MODE? log + succeeded; return.  │
                        │      Else:                                  │
                        │        StankoffClient.sendInbox(...)        │
                        │           POST signed webhook (HMAC)        │
                        │           ts generated AT call time         │
                        │   6. 200/202 → succeeded                    │
                        │      transient (5xx/timeout/clock-drift)    │
                        │              → throw → Messenger retries    │
                        │      permanent (4xx, sig, body too big)     │
                        │              → throw Unrecoverable          │
                        │              → Messenger fails to `failed`  │
                        └──────────────────────┬──────────────────────┘
                                               │
                                               ▼
                                  https://preprod.stankoff.ru/...
```

## Why outbox + Messenger (not just Messenger, not just outbox)

The single most important reliability question: *how do we guarantee that
every ticket is eventually delivered, while never failing the user-facing
POST?*

Three options were considered:

1. **Direct HTTP from the Processor.** Simplest, but Stankoff outages,
   network blips, and slow responses block the user. **Rejected.**
2. **Just Messenger.** Doctrine transport already persists messages to a
   table — isn't that an outbox? Almost. But a `bus->dispatch()` failure
   (DB outage at exactly the wrong moment, transport bug, deserialization
   problem) means the ticket exists with no record that it should have
   been forwarded. **Rejected.**
3. **Outbox + Messenger.** A first-class business row
   (`integration_outbox_event`) is created in the *same DB transaction* as
   the ticket. The Messenger dispatch is a notification on top. If the
   dispatch fails, the outbox row is still there — a future backfill
   command can find unprocessed rows and re-dispatch. **Chosen.**

The cost: one extra table, one extra row write per ticket. Negligible.

The benefit: a clear, debuggable, replayable record of every delivery
attempt — visible in SQL, not buried in serialized Messenger envelopes.

## Why two feature flags

`STANKOFF_INTEGRATION_ENABLED` and `STANKOFF_SHADOW_MODE` cover three
distinct rollout questions:

|                                         | `ENABLED=false` | `ENABLED=true SHADOW=true` | `ENABLED=true SHADOW=false` |
|-----------------------------------------|-----------------|----------------------------|-----------------------------|
| Outbox row created on POST?             | no              | yes                        | yes                         |
| Async dispatch happens?                 | no              | yes                        | yes                         |
| Worker builds payload + signs it?       | n/a             | yes                        | yes                         |
| Worker performs HTTP call to Stankoff?  | n/a             | **no** (logs payload)      | yes                         |

This separation allows three deployments:

- **Phase A** — code deployed but inert (`ENABLED=false`). Verifies that
  shipping the integration code does not regress anything.
- **Phase B** — shadow on real traffic (`ENABLED=true, SHADOW=true`).
  Verifies that the field mapping works on every real ticket shape (for
  example, that `orderData.contactPhone` is actually present, that
  `authorName` resolves to a known employee, etc.) **without** any
  external side-effect.
- **Phase C/D** — live.

The third sub-flag `STANKOFF_FILES_ENABLED` exists because file upload is
the heaviest, slowest path (download from our S3, upload to theirs, confirm)
and the most likely to surface MIME / size limit issues. Decoupling it lets
us go live with text-only webhooks first.

## Why PostgreSQL `pg_notify` trigger on `messenger_messages`

Doctrine Messenger transport polls `messenger_messages` for new rows.
Default poll interval is 1 second when idle, but **the worker only checks
the `delayed` queue every 60 seconds** (`check_delayed_interval`), which
means a retry can sit unattended for nearly a minute.

The trigger on the table issues `pg_notify('messenger_messages', queue_name)`
on every insert/update. The Doctrine transport listens via `LISTEN`, so the
worker is woken instantly. This:

- removes the up-to-1s polling latency for fresh messages,
- keeps the up-to-60s wait acceptable for delayed retries (their backoff
  is 5–60s anyway, so the overlap is fine),
- has effectively zero CPU cost — it's a single trigger.

The migration creates the function and trigger explicitly because
`auto_setup=0` is set on the DSN — we want the schema under migration
control, not magically created on first send.

For local-only testing of retry timing we override the DSN in `.env.local`
to `?check_delayed_interval=1000` — **never** do that on prod (it would
hammer the DB).

## Why a single worker (concurrency = 1)

Stress-tested empirically (see `stress-test-findings.md` §S1):
Stankoff's inbox endpoint reliably handles ~5–10 concurrent requests, then
returns `500 INTERNAL` for the rest. Our single worker naturally throttles
outbound calls to 1-at-a-time, fully sidestepping that failure mode.

If/when Stankoff fixes the concurrency bug, scaling horizontally is one
line in `compose.prod.yaml` (`replicas: 3`) — Messenger doctrine transport
uses `SELECT FOR UPDATE SKIP LOCKED` so concurrent workers do not double-deliver.

## Idempotency: how it actually works

Two layers cooperate:

**Our side — stable idempotency-key.** We generate a UUIDv7 hex (32 chars)
once when the outbox row is created and **persist it**. Every retry of the
same Messenger message reads the *same* key from the same outbox row.
Implementation: `IntegrationOutboxEvent::idempotencyKey`, generated in
`SupportTicketCreateProcessor` via `bin2hex(Uuid::v7()->toBinary())`.

**Their side — replay semantics.** Stankoff guarantees that two requests
with the same `Idempotency-Key` produce one record on their side. The
second request returns `200 OK` with `replay: true` instead of `202 queued`.
We treat both as success.

Net effect: it is impossible to create a duplicate ticket on their side
through retries, and it is impossible to lose a ticket through transient
errors on either side.

## Why the timestamp is generated *at HTTP call time*, not at message creation

Stankoff enforces a **±5 minute replay window** on `X-Integration-Timestamp`.
If we generated the timestamp when the Messenger message was created and
the message sat in the queue for 6 minutes (which can happen during a
network outage and worker recovery), every retry would fail with
`401 REPLAY_WINDOW_EXCEEDED`.

By generating the timestamp inside `StankoffClient::sendInbox()` we
guarantee that the timestamp is fresh on each attempt. The trade-off:
the signature is also computed at call time, not cacheable. Since signing
is `hash_hmac` which is microseconds, this is fine.

## Files API: 3-phase upload, idempotent on retry

```
   ticket has N media (SupportTicketMedia)
            │
            ▼
   for each media not in outbox.uploaded_file_ids:
       ┌──── 1. download from our Yandex S3 (existing path) ────┐
       │ 2. POST /files/request-upload                          │
       │       → fileId + presigned uploadUrl + uploadFields    │
       │ 3. multipart POST presigned URL                        │
       │       Content-Type MUST match (Q3.3 enforcement)       │
       │ 4. POST /files/{fileId}/confirm                        │
       │ 5. outbox.uploaded_file_ids[mediaId] = fileId          │
       │    (persisted IMMEDIATELY so retry skips this media)   │
       └────────────────────────────────────────────────────────┘
            │
            ▼
   payload.attachment_ids = [fileId1, fileId2, ...]
   POST inbox webhook
```

Stankoff's Files API is **not idempotent** (no Idempotency-Key on
request-upload). To avoid uploading the same file twice on a Messenger
retry, the outbox tracks `[mediaId => fileId]` keyed by *our* media id —
not by index — so adding/removing media between attempts does not corrupt
the mapping. See `IntegrationOutboxEvent::rememberUploadedFile()`.

## Error classification

`ErrorClassifier::classify()` is the single place that decides whether a
non-success response is retryable.

| Stankoff response                                  | Class    | Action |
|----------------------------------------------------|----------|--------|
| 5xx (any)                                          | Transient | retry |
| **408 Request Timeout**                            | Transient | retry (RFC 7231 §6.5.7) |
| **429 Too Many Requests**                          | Transient | retry (Stankoff's planned Phase 2 rate-limit) |
| 401 + `errorCode = INVALID_TIMESTAMP`              | Transient | retry (next attempt has fresh ts) |
| 401 + `errorCode = REPLAY_WINDOW_EXCEEDED`         | Transient | retry |
| 401 + other codes (`INVALID_SIGNATURE`, `MISSING_HEADERS`, …) | Permanent | fail to `failed` queue |
| 400, 404, 413, 422                                 | Permanent | fail |
| Any other 4xx (default)                            | Permanent | fail |
| Network error, DNS fail, connect refused, timeout  | Transient | retry (raised by HttpClient) |

`StankoffPermanentException` extends `UnrecoverableMessageHandlingException`,
so simply throwing it halts Messenger retry without needing a separate stamp.

## EmployeeResolver: read-only CSV, in-memory

`authorName` in our `SupportTicket` is a **string** (e.g. `"Дмитрий Мыслюк"`).
Stankoff's webhook expects `author_employee_id` as an **int**. Bridge: a
static CSV (`api/config/integrations/stankoff_employees.csv`) with all 161
known employees, loaded once at service construction.

Why CSV and not a DB table:

- This is a temporary bridge until Stankoff exposes a stable employees API.
- Adding a new alias means redeploying with an updated CSV, not running
  a migration. Faster and reversible.
- The user's explicit requirement: "никуда в БД не сохранять, временное
  решение".
- Cross-checked against 116 production tickets: 22 of 24 unique authorNames
  match 1:1 (97% of tickets). The 2 unmatched authors fall back to
  `STANKOFF_FALLBACK_EMPLOYEE_ID = 148` (current operator).

Lookup is normalized for case, ё/е, whitespace, and supports reversed
"firstName lastName"/"lastName firstName".

## What it doesn't do (intentional scope)

- **No re-send on ticket update** — Stankoff only supports
  `support_ticket.created.v1`. If we change a ticket's status, no event
  fires. When Stankoff adds an `updated` event we'll add a second
  Messenger message + handler.
- **No file-only updates** — if media is added to a ticket *after* the
  webhook has been sent, the new media won't be forwarded. (POST
  /support_tickets/{id}/media is a separate endpoint that runs after
  ticket creation.) This is a known limitation; the practical workaround
  is to ensure media is attached on the original POST flow before this
  becomes a problem at scale.
- **No GraphQL-specific behaviour** — API Platform 4.x shares the
  Processor between REST and GraphQL, so both entry points are covered
  automatically.
- **No worker concurrency tuning** — see "Why a single worker" above.

## Symfony / library versions used

| Package                | Version | Why                                    |
|------------------------|---------|----------------------------------------|
| symfony/messenger      | 7.3.x   | matches the rest of Symfony in project |
| symfony/uid            | 7.3.x   | UUIDv7 for sortable idempotency-keys   |
| symfony/doctrine-messenger | 7.3.x | doctrine transport implementation    |
| symfony/http-client    | 7.3.x (already present) | webhook + Files API HTTP   |

No third-party outbox libraries used — the pattern is small enough that
inlining it makes the code clearer than depending on a generic framework.

## Test coverage

| Layer | What                                              | Count |
|-------|---------------------------------------------------|-------|
| Unit  | SignatureFactory, EmployeeResolver, PayloadBuilder, ErrorClassifier | 40 |
| Local E2E | shadow / live happy / retry on 503 / idempotency replay (against `fake-stankoff` container) | 4 scenarios |
| Live E2E (preprod) | happy 202 / replay 200 / bad signature 401 | 3 scenarios |
| Stress (preprod) | HMAC, timestamp, idem-key, headers, body size, payload edges, 30-concurrent, 20-race | 88 probes + 50 concurrent |

See `stress-test-findings.md` for what the stress test taught us.

## Useful entry points for future change

- **Adding a new event type** (e.g. status change): new Message class +
  Handler, route to `stankoff_integration` transport in `messenger.yaml`,
  emit dispatch from the relevant Controller (e.g.
  `SupportTicketController::changeStatus`).
- **Updating the employee map**: edit
  `api/config/integrations/stankoff_employees.csv`, redeploy. Worker
  reads the file at boot.
- **Changing payload shape**: edit `PayloadBuilder::build()`. All field
  mapping logic lives there.
- **Adjusting retry strategy**: `api/config/packages/messenger.yaml`
  → `transports.stankoff_integration.retry_strategy`.
- **Replacing fake-stankoff** with a real test double: edit
  `fake-stankoff/server.php`. The harness endpoints
  (`/__/requests`, `/__/scenario`, `/__/idempotency-replay`) are stable.
