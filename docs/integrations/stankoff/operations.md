# Operations runbook — Stankoff webhook integration

> Audience: anyone deploying, monitoring, or troubleshooting the integration
> in production (currently the same person who built it). Keep this open
> when you flip a flag.

## Quick map

| You want to…                           | Section                  |
|----------------------------------------|--------------------------|
| Deploy code changes to prod            | "Deployment"             |
| Turn the integration on                | "Phased rollout"         |
| Turn it off in a hurry                 | "Kill switch"            |
| See what's pending / stuck             | "Inspecting state"       |
| Re-try a failed message                | "Failed messages"        |
| Update the employee mapping            | "Updating the CSV"       |
| Diagnose a delivery problem            | "Troubleshooting"        |

---

## Deployment

There is no CI/CD on this project. Deployments are manual via SSH. Two-step:

### 1. Locally — verify before push

```bash
# Run tests
docker compose exec php vendor/bin/phpunit tests/Unit/Integration/Stankoff/

# Static analysis
docker compose exec php vendor/bin/phpstan analyse --no-progress src/Integration

# Local E2E against fake-stankoff (sanity)
docker compose exec php php bin/console app:stankoff:smoke

# Push when green
git push origin main
```

### 2. On the prod server (`new.stankoff.ru`)

```bash
ssh <prod-host>
cd /path/to/stankoff-new   # the deploy directory

# Pull
git fetch --all
git status                 # MUST show clean working tree before next step
git pull --ff-only origin main

# Inspect what's about to change before touching containers
git log --oneline @{1}..HEAD
git diff --stat @{1}..HEAD

# Build new image (it's the same image for php and php-worker)
docker compose -f compose.yaml -f compose.prod.yaml build php

# Run migrations BEFORE bringing up the worker (the new tables must exist)
docker compose -f compose.yaml -f compose.prod.yaml run --rm php \
  php bin/console doctrine:migrations:migrate --no-interaction

# Bring up everything (php, php-worker, others as needed)
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Verify
docker compose -f compose.yaml -f compose.prod.yaml ps
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=50 php-worker
```

The new `php-worker` container will start in the **default disabled** state
(`STANKOFF_INTEGRATION_ENABLED=false`) — it consumes the queue, but no
ticket dispatches anything. Production behaviour is byte-identical to
pre-integration.

---

## Phased rollout

Each phase is one env-var flip + container restart.

```bash
# Edit the prod env file (location depends on your deploy)
# Or override on docker compose level:

# === Phase B — Shadow ===
# Code creates outbox rows + dispatches messages. Worker builds payload + signs,
# writes it to the log, but DOES NOT call Stankoff.
echo "STANKOFF_INTEGRATION_ENABLED=true"  >> .env.prod
echo "STANKOFF_SHADOW_MODE=true"          >> .env.prod
docker compose -f compose.yaml -f compose.prod.yaml up -d --force-recreate php php-worker

# Watch the logs for a few hours / a day
docker compose -f compose.yaml -f compose.prod.yaml logs -f php-worker | grep stankoff
# Expected lines: "stankoff: SHADOW MODE — payload built but NOT sent"
# Verify in the log that field mappings look right (especially author_employee_id,
# order_item_ids parsed from selectedItems, contactPhone/Email present where expected).

# === Phase C — Live, no files ===
# Edit env: STANKOFF_SHADOW_MODE=false
docker compose -f compose.yaml -f compose.prod.yaml up -d --force-recreate php php-worker

# === Phase D — Live with files ===
# Edit env: STANKOFF_FILES_ENABLED=true
docker compose -f compose.yaml -f compose.prod.yaml up -d --force-recreate php php-worker
```

---

## Kill switch

If Stankoff explodes, or our integration behaves unexpectedly:

```bash
# Edit env: STANKOFF_INTEGRATION_ENABLED=false
docker compose -f compose.yaml -f compose.prod.yaml restart php php-worker
```

Effect:
- New tickets persist exactly as they did pre-integration. **No outbox row,
  no dispatch.** Behaviour is byte-identical.
- Worker keeps consuming any messages already in the queue. To freeze
  outbound traffic completely, also stop the worker:

```bash
docker compose -f compose.yaml -f compose.prod.yaml stop php-worker
```

When ready to resume, flip the flag back and `up -d` the worker.

---

## Inspecting state

```bash
# Outbox summary
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  psql -U app -d app -c "
    SELECT status, count(*),
           min(created_at) AS oldest,
           max(succeeded_at) AS newest_success
    FROM integration_outbox_event
    GROUP BY status;"

# Currently pending / in_progress (rows that haven't succeeded)
docker compose ... exec database psql -U app -d app -c "
    SELECT id, aggregate_id, status, attempts_count, last_attempt_at, last_error
    FROM integration_outbox_event
    WHERE status IN ('pending', 'in_progress', 'permanently_failed')
    ORDER BY created_at DESC LIMIT 20;"

# Messenger queue state
docker compose ... exec database psql -U app -d app -c "
    SELECT queue_name, count(*),
           min(available_at) AS next_due,
           min(delivered_at) AS oldest_delivered
    FROM messenger_messages
    GROUP BY queue_name;"

# Worker process
docker compose ... ps php-worker
docker compose ... exec php-worker php bin/console messenger:stats
```

---

## Failed messages

Messages that exhausted retries (default 5 attempts) land on the `failed`
transport. They are **not** auto-retried.

```bash
# List
docker compose ... exec php-worker php bin/console messenger:failed:show

# Inspect a specific one
docker compose ... exec php-worker php bin/console messenger:failed:show 42

# Retry one (will go through normal handler → may succeed if the issue is fixed)
docker compose ... exec php-worker php bin/console messenger:failed:retry 42

# Retry all
docker compose ... exec php-worker php bin/console messenger:failed:retry --all

# Discard (only after you've understood why it failed)
docker compose ... exec php-worker php bin/console messenger:failed:remove 42
```

Note: `messenger:failed:retry` does **not** reset the outbox row's status
back to `pending`. Re-dispatching a message whose outbox is already
`permanently_failed` will short-circuit (the handler's idempotent guard).
If you want a true retry of a permanent failure, **first** reset the row:

```sql
UPDATE integration_outbox_event SET status = 'pending', last_error = NULL
WHERE id = '<outbox-uuid>';
```

…then `messenger:failed:retry`.

---

## Updating the employee CSV

The webhook needs to map `authorName` → Stankoff `employee_id`. The
mapping lives in `api/config/integrations/stankoff_employees.csv`.

To add or fix a mapping:

1. Edit the CSV locally. Format:
   ```
   employeeId,firstName,lastName
   148,Эдуард,Сарваров
   ```
2. Commit and push.
3. On prod: `git pull` then **restart the worker** (the file is read at
   service construction):
   ```bash
   docker compose ... restart php-worker
   ```

The resolver normalises `ё→е`, case, and whitespace, and accepts both
`firstName lastName` and `lastName firstName` orderings, so most edits
are obvious. If `authorName` doesn't match any row, the
`STANKOFF_FALLBACK_EMPLOYEE_ID` (default `148` — Эдуард Сарваров) is used
and a `WARNING` is logged.

---

## Troubleshooting

### "Tickets are being created but nothing is reaching Stankoff"

1. Is the integration enabled?
   ```bash
   docker compose ... exec php-worker env | grep STANKOFF_INTEGRATION_ENABLED
   ```
2. Is shadow mode on?
   ```bash
   docker compose ... exec php-worker env | grep STANKOFF_SHADOW_MODE
   ```
3. Is the worker actually running?
   ```bash
   docker compose ... ps php-worker
   docker compose ... logs --tail=20 php-worker | grep -v setfacl
   ```
4. Are messages backing up?
   ```bash
   # If queued count is high and ticking, worker is alive but slow.
   # If it's not changing, the worker is stuck or stopped.
   docker compose ... exec database psql -U app -d app \
     -c "SELECT count(*) FROM messenger_messages WHERE queue_name='stankoff_integration';"
   ```

### "Most messages fail with `INVALID_SIGNATURE`"

- Check `STANKOFF_HMAC_SECRET` matches what Stankoff issued.
- Check the timestamp in worker logs vs. server clock — drift > 5min
  causes `REPLAY_WINDOW_EXCEEDED`. Ensure NTP is configured:
  `timedatectl status` should show `synchronized: yes`.

### "Some messages fail with 500 INTERNAL"

- Stankoff's API has a known concurrency degradation above ~5 parallel
  requests (see `stress-test-findings.md` §S1). With our default single
  worker this should not happen unless something else is also pounding
  their endpoint at the same time.
- These are classified as Transient — they'll retry with backoff. Check
  `outbox.attempts_count`. If a message is reaching `attempts=5` (default
  max retries) and then permanently failing, that's a deeper issue —
  `messenger:failed:show` will have details.

### "We see a `CRITICAL` log line: `stankoff: integration dispatch failed, ticket created without outbox`"

This means the user-facing POST succeeded (the ticket exists) but the
integration dispatch failed (DB issue, transport down, etc.). The ticket
is now invisible to Stankoff. Steps:

1. Find the ticket id from the log.
2. Manually create the outbox row (until a backfill command is added):
   ```sql
   INSERT INTO integration_outbox_event
     (id, event_type, aggregate_type, aggregate_id, idempotency_key, status, uploaded_file_ids, attempts_count, created_at)
   VALUES
     (gen_random_uuid(), 'stankoff.support_ticket.created.v1',
      'SupportTicket', <ticket_id>, encode(gen_random_bytes(16), 'hex'),
      'pending', '{}'::json, 0, NOW());
   ```
3. Dispatch a message for it (no built-in command yet — use the smoke
   command pattern or run `bin/console messenger:dispatch` once that's
   wired up). Easier interim: just hit `/support_tickets` with the same
   payload to create a *new* ticket — Stankoff will see two events with
   different IDs but the user impact is small.

(A `app:stankoff:backfill` command is on the TODO list; once added it
will scan for tickets without outbox rows and remediate automatically.)

---

## Common gotchas

- **Cache mismatch between `php` and `php-worker`** — they have separate
  Symfony caches. After a config change, restart **both** containers.
- **Anonymous volume on `/app/var`** — both containers have one. If you
  see weird "stale config" issues, `docker compose ... down --volumes`
  and `up -d` will reset (will lose Caddy storage; fine for prod).
- **Worker memory creep** — bounded by `--memory-limit=256M`; the worker
  exits cleanly when it gets close, and Compose restarts it. Should
  never reach OOM.
- **Time** — server clock drift > 5 min breaks every webhook. NTP must
  be running.
