# Security — Stankoff webhook integration

> What is signed, what is masked, where the secrets live, and what we
> rely on. Audience: anyone reviewing for security or compliance.

## Threat model in one paragraph

The integration sends ticket data — including (optionally) customer
contact name, phone, and email — to an external system over HTTPS. The
adversaries we care about are:

1. **Network observers / MITM** — between us and Stankoff. Mitigated by
   TLS + HMAC.
2. **Compromised credentials** — `STANKOFF_HMAC_SECRET` or
   `STANKOFF_API_KEY` leaking. Mitigated by env-var-only storage,
   `.env*.local` gitignored, no logging of secret values.
3. **Replay** — old captured request being replayed. Mitigated by
   timestamp + 5-min window.
4. **PII in our own logs** — accidental disclosure of customer phone /
   email when log files are shared. Mitigated by mask-on-log.

There are also residual risks **on the Stankoff side** documented in
`stress-test-findings.md` (notably S2 — signature does not bind to body
bytes), but those are not under our control.

## What is signed (HMAC-SHA256)

The header `X-Integration-Signature: sha256=<hex>` is computed as:

```
signature = HMAC_SHA256(
    secret = STANKOFF_HMAC_SECRET,
    message = timestamp + "." + raw_body_bytes
)
hex_lowercase
```

Implementation: `App\Integration\Stankoff\Client\SignatureFactory::sign()`.

**Important byte-level invariants** that we rely on:

- `raw_body_bytes` is the exact bytes that go on the wire (output of
  `json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)`).
  We never re-serialize between signing and sending.
- `timestamp` is the same string as `X-Integration-Timestamp`. Generated
  via `SignatureFactory::timestampNow()` — `gmdate('Y-m-d\TH:i:s.v\Z')`
  for ISO 8601 with millisecond precision and 'Z' suffix.
- The timestamp is generated **at HTTP-call time**, not at message
  creation time. This guarantees a fresh timestamp on every Messenger
  retry — staying within Stankoff's ±5 minute replay window even after
  long backoff delays.

## What we use to compare signatures (in tests)

Our `SignatureFactoryTest` and any future verifier code use
`hash_equals()` to avoid timing-attack leakage. The receiver side
(Stankoff) is their concern.

## Secret storage

| Secret                  | Where                           | Never                       |
|-------------------------|---------------------------------|------------------------------|
| `STANKOFF_HMAC_SECRET`  | Env var (process or compose)    | In code, in DB, in logs      |
| `STANKOFF_API_KEY`      | Env var                         | Same                         |
| `APP_SECRET`            | Env var (Symfony)               | Same                         |
| `POSTGRES_PASSWORD`     | Env var                         | Same                         |
| `KEYCLOAK_*_PASSWORD`   | Env var                         | Same                         |

Local development uses `api/.env.local` (gitignored). Production injects
through the orchestration environment (e.g. Docker Compose `.env` file
that is **not** in the deploy directory's git working tree, or the
`environment:` block in `compose.prod.yaml`).

`.env.local` is covered by `api/.gitignore` (`/.env.local`) and verified
not to leak via:

```bash
git check-ignore -v api/.env.local
```

We never commit secrets. We never write secrets to logs. There is no
"debug" code path that prints the HMAC secret — `SignatureFactory` only
holds it in a private property and uses it through `hash_hmac()`.

## PII masking in logs

In shadow mode and on every successful/failed delivery, the handler
writes a structured log line. To prevent accidental leak of customer
contact data through aggregated log searches / shared log files,
phone and email are masked **before** the log call:

```php
// ForwardSupportTicketCreatedHandler::maskPii()
phone "+7(999)123-45-67"   →  "+*(***)***-*4567"
email "ivan@example.ru"     →  "i***@example.ru"
```

The original values are still sent on the wire (Stankoff needs them) —
the masking is only applied to the in-memory copy that gets logged.

What is **not** masked in logs:
- `subject` and `description` of the ticket — these may contain
  customer-typed text that could include PII. We rely on the existing
  treatment of these fields in the rest of the application; we do not
  add extra masking here.
- `client_contact_name` — first name only is generally not considered
  PII at the level requiring masking. Adjust if compliance disagrees.
- Internal IDs (ticket id, employee id, idempotency-key, outbox uuid).

## TLS

`HttpClientInterface` (Symfony) is used with default settings:
- `verify_peer = true`
- `verify_host = true`
- TLS 1.2+ negotiated by libcurl

We never disable certificate verification. We do not pin certificates
(would break on Stankoff's cert rotation). If pin-on-prod is later
required, add `peer_fingerprint` option to the relevant `request()` call.

## Replay protection

Two layers:

1. **Stankoff's window** (`±5 min` on `X-Integration-Timestamp`). A
   captured request older than 5 minutes is rejected with
   `401 REPLAY_WINDOW_EXCEEDED`.
2. **Our idempotency-key**. Every outbox row has a unique UUIDv7
   (32 hex chars). If an attacker replayed a captured request *within*
   the 5-min window, Stankoff would dedupe by idempotency-key and
   return `200 replay:true` — no second record.

Within the 5-min window an attacker could:
- Re-trigger the same record again (no harm — Stankoff dedupes).
- **Forward a request to Stankoff that was destined for them** — but
  HMAC ensures it was generated with our secret, so this is exactly
  the legitimate request anyway.

What an attacker **cannot** do:
- Forge a new request with a future timestamp (no secret).
- Modify the payload bytes after signing (would invalidate the HMAC) —
  *but see S2 in `stress-test-findings.md` — Stankoff does not currently
  detect this. Mitigation rests on TLS in transit.*

## Idempotency-key uniqueness

`idempotency_key` is `UNIQUE` in the `integration_outbox_event` table at
the DB level. Two outbox rows can never share a key. The key is generated
from `Uuid::v7()->toBinary()` → `bin2hex()` (32-char lowercase hex), which
is the recommended Symfony 7.3 way and produces ~`2^48` time-ordered
randoms per millisecond (no collisions in any realistic workload).

## Audit trail

Every delivery attempt updates `integration_outbox_event`:
- `attempts_count` increments
- `last_attempt_at` updates
- `last_error` carries the most recent failure reason (for transient or
  permanent path)
- `status` transitions: `pending → in_progress → succeeded` (or
  `permanently_failed`)

The Messenger queue (`messenger_messages`) is a secondary audit path.
Failed messages on the `failed` transport include the full envelope and
the exception trace.

For long-term audit (e.g., SOC2-style), schedule a job to dump
`integration_outbox_event` to cold storage periodically — currently not
implemented, document as future work if needed.

## Dependencies

| Library                | Purpose                  | Notes                       |
|------------------------|--------------------------|------------------------------|
| `hash_hmac` / `hash_equals` | HMAC-SHA256, constant-time compare | PHP core, well-audited |
| `symfony/http-client`  | TLS HTTPS, multipart     | Already in project           |
| `symfony/uid`          | UUIDv7 generation        | Pinned to 7.3.x              |
| `symfony/messenger`    | At-least-once delivery   | Pinned to 7.3.x              |

No third-party crypto. No homegrown TLS / signature schemes.

## What to do on suspected secret leak

```bash
# 1. Revoke at Stankoff (their team must rotate STANKOFF_HMAC_SECRET and
#    STANKOFF_API_KEY on their side)
# 2. Update env on prod
ssh <prod>
edit .env  # set new STANKOFF_HMAC_SECRET / STANKOFF_API_KEY
docker compose -f compose.yaml -f compose.prod.yaml up -d --force-recreate php php-worker
# 3. Verify with a smoke probe
docker compose ... exec php php bin/console app:stankoff:smoke
# 4. Re-run failed messages with the new secret
docker compose ... exec php-worker php bin/console messenger:failed:retry --all
# 5. Audit logs for any unauthorized 202 responses around the suspected
#    leak window — outbox table has aggregate_id of every delivery
```

## What to do on key rotation by Stankoff (planned)

Same steps as above, minus step 1. Coordinate with Stankoff team —
provide them a rollover window where both old and new secrets are
accepted on their side.
