# Stankoff webhook integration

Forwarding of every newly created `SupportTicket` to the central Stankoff platform
(`preprod.stankoff.ru`) as an asynchronous, signed, idempotent webhook —
optionally with media attachments uploaded via Stankoff's Files API.

This directory contains the complete documentation set for the integration.

## Quick navigation by audience

| You are…                                   | Start here                                |
|--------------------------------------------|-------------------------------------------|
| New engineer joining the project           | `architecture.md`                         |
| On-call / DevOps deploying or troubleshooting | `operations.md`                        |
| Reviewing security or compliance           | `security.md`                             |
| Curious why the design looks the way it does | `stress-test-findings.md` + `architecture.md` |
| Just want to turn it on                    | "Phased rollout" section below            |

## What it does, in one paragraph

When a `POST /support_tickets` is processed (HTTP or GraphQL), the original
ticket is persisted exactly as before. **In addition**, when integration is
enabled, an `integration_outbox_event` row is created in the same DB
transaction and an async Messenger job is dispatched. A separate `php-worker`
container picks up the job, optionally uploads media to Stankoff Files API
(3-phase: request-upload → S3 multipart → confirm), then sends a HMAC-SHA256
signed webhook to `https://preprod.stankoff.ru/api/v1/integrations/inbox/{id}`.
On 5xx / network / timeout / clock-drift the message is retried with
exponential backoff, **with the same idempotency key**, so Stankoff sees
exactly one logical event regardless of retries.

## Key properties

- **At-least-once delivery** — outbox row + Messenger queue + Stankoff's
  idempotency-key make duplicates impossible at the receiver, while losses
  on the sender side are bounded to "log the row, fix manually".
- **Zero impact on the user-facing POST** — every interaction with the
  integration is wrapped in `try/catch` and a feature-flag short-circuit.
  A bug in our integration code or a Stankoff outage cannot fail a ticket
  creation.
- **Two feature flags** for safe rollout:
  `STANKOFF_INTEGRATION_ENABLED` (master) and `STANKOFF_SHADOW_MODE`
  (build payload + log, but don't send).
- **Modern PHP / Symfony 7.3 stack** — `symfony/messenger` 7.3,
  `symfony/uid` UUIDv7, native `HttpClientInterface`, attribute-based
  routing and DI. No third-party "outbox" libraries.

## Phased rollout

The integration ships **disabled** (`STANKOFF_INTEGRATION_ENABLED=false`).
Recommended rollout order:

| Phase | `INTEGRATION_ENABLED` | `SHADOW_MODE` | `FILES_ENABLED` | Goal |
|-------|-----------------------|---------------|-----------------|------|
| **A. Off**  | `false` | — | — | Code deployed but inert. Default state. |
| **B. Shadow** | `true`  | `true`  | `false` | Build payload + log it. **No HTTP**. Validate field mapping on real traffic. |
| **C. Live (no files)** | `true` | `false` | `false` | Real webhook to Stankoff. Attachments not uploaded. |
| **D. Full** | `true`  | `false` | `true`  | Everything, including media via Files API. |

Each phase is a single env-var flip + worker restart (~10 sec, no rebuild).

See `operations.md` for the exact commands.

## Required environment variables

| Var                              | Where used                       | Example / Notes                                |
|----------------------------------|----------------------------------|------------------------------------------------|
| `STANKOFF_INTEGRATION_ENABLED`   | Processor (gate)                 | `false` by default                             |
| `STANKOFF_SHADOW_MODE`           | Handler (skip HTTP, log payload) | `true` for Phase B                             |
| `STANKOFF_FILES_ENABLED`         | Handler (skip Files API)         | `false` until tested with real media           |
| `STANKOFF_BASE_URL`              | StankoffClient, FilesUploader    | `https://preprod.stankoff.ru`                  |
| `STANKOFF_INTEGRATION_ID`        | StankoffClient (URL path)        | `int_01kq7fd1d8eb48hqnf4cm34wmy`               |
| `STANKOFF_HMAC_SECRET`           | SignatureFactory                 | **secret** — do not commit                     |
| `STANKOFF_API_KEY`               | FilesUploader (Bearer)           | **secret** — do not commit                     |
| `STANKOFF_FALLBACK_EMPLOYEE_ID`  | EmployeeResolver                 | `148` (Эдуард Сарваров — current operator)     |
| `MESSENGER_TRANSPORT_DSN`        | Messenger doctrine transport     | `doctrine://default?auto_setup=0`              |

For local dev, set them in `api/.env.local` (gitignored). For production,
inject through the orchestration env (Docker Compose env file or k8s secret).

## File map

```
api/
├── config/
│   ├── packages/messenger.yaml          # transport + retry strategy
│   ├── packages/doctrine.yaml           # +mapping for outbox entity
│   └── integrations/
│       └── stankoff_employees.csv       # 161-row authorName→employeeId map
├── migrations/
│   └── Version20260429120000.php        # outbox + messenger_messages tables
├── src/
│   ├── Integration/Stankoff/
│   │   ├── Client/                      # HTTP, signing, error classification
│   │   ├── Files/FilesUploader.php      # 3-phase Files API upload
│   │   ├── Message/ForwardSupportTicketCreated.php
│   │   ├── MessageHandler/ForwardSupportTicketCreatedHandler.php
│   │   ├── Outbox/                      # entity + repo + status enum
│   │   └── Payload/                     # PayloadBuilder + EmployeeResolver
│   └── Command/StankoffSmokeCommand.php # dev-only: app:stankoff:smoke
└── tests/
    └── Unit/Integration/Stankoff/       # 40 unit tests

compose.yaml                             # +php-worker service
compose.override.yaml                    # +fake-stankoff for E2E (dev only)
compose.prod.yaml                        # +php-worker prod overrides
fake-stankoff/server.php                 # PHP-built-in fake Stankoff API (dev/E2E)
```

## Where decisions came from

A summary of the trade-offs and tests that drove the design lives in
`architecture.md` (the "why"), and the empirical observations from a
~140-probe stress test against `preprod.stankoff.ru` are recorded in
`stress-test-findings.md` (the "what we learned about Stankoff's API").

Read those two when you need to extend or change the integration —
they answer almost every "why didn't you just…" question.
