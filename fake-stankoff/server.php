<?php

declare(strict_types=1);

/**
 * Fake Stankoff integration server for E2E testing.
 *
 * Implements the contract from the partner doc:
 *   - POST /api/v1/integrations/inbox/{integrationId}
 *       validates HMAC signature + headers, returns 202 by default
 *   - POST /api/v1/integrations/files/request-upload
 *       returns presigned upload URL pointing back to this same server
 *   - POST /fake-s3/{uploadKey}  (the "presigned" URL)
 *       accepts multipart, returns 204
 *   - POST /api/v1/integrations/files/{fileId}/confirm
 *       returns 200 with status=ready
 *   - GET /health -> {"status":"ok"}
 *
 * Test harness endpoints (under /__):
 *   - GET  /__/requests       — list of all received requests with bodies/headers
 *   - POST /__/reset          — clear request log AND scenario state
 *   - POST /__/scenario       — body {"endpoint":"inbox","status":503,"count":2}
 *                               makes next N inbox calls return given status
 *   - POST /__/idempotency-replay  — body {"key":"..."}
 *                               makes that idempotency-key return 200 with replay:true
 *
 * State stored in /tmp/fake-state/ as JSON files (concurrent-safe via flock).
 */

// HMAC secret read from env — must match the one our StankoffClient signs with.
// Default is a dummy so the file is safe to commit. For a real E2E run wire
// `STANKOFF_HMAC_SECRET` (from your gitignored .env.local) into this container
// via compose.override.yaml's fake-stankoff service.
define('HMAC_SECRET', getenv('STANKOFF_HMAC_SECRET') ?: 'dummy-fake-server-secret-do-not-use-in-prod');
const STATE_DIR = '/tmp/fake-state';
const REQUESTS_FILE = STATE_DIR . '/requests.json';
const SCENARIOS_FILE = STATE_DIR . '/scenarios.json';
const REPLAY_KEYS_FILE = STATE_DIR . '/replay-keys.json';

// Bootstrap state
@mkdir(STATE_DIR, 0777, true);
foreach ([REQUESTS_FILE => '[]', SCENARIOS_FILE => '{}', REPLAY_KEYS_FILE => '{}'] as $f => $init) {
    if (!file_exists($f)) {
        file_put_contents($f, $init);
    }
}

// PHP built-in server: route through this single file
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rawBody = file_get_contents('php://input') ?: '';
$headers = collectHeaders();

logRequest($method, $path, $headers, $rawBody);

try {
    if ($method === 'GET' && $path === '/health') {
        respond(200, ['status' => 'ok']);
    }

    if ($method === 'GET' && $path === '/__/requests') {
        respond(200, json_decode((string)file_get_contents(REQUESTS_FILE), true));
    }
    if ($method === 'POST' && $path === '/__/reset') {
        file_put_contents(REQUESTS_FILE, '[]');
        file_put_contents(SCENARIOS_FILE, '{}');
        file_put_contents(REPLAY_KEYS_FILE, '{}');
        respond(200, ['status' => 'reset']);
    }
    if ($method === 'POST' && $path === '/__/scenario') {
        $req = json_decode($rawBody, true) ?: [];
        withLock(SCENARIOS_FILE, function (array $st) use ($req) {
            $endpoint = (string)($req['endpoint'] ?? 'inbox');
            $st[$endpoint] = [
                'status' => (int)($req['status'] ?? 500),
                'errorCode' => $req['errorCode'] ?? null,
                'count' => (int)($req['count'] ?? 1),
            ];
            return $st;
        });
        respond(200, ['status' => 'scenario_set']);
    }
    if ($method === 'POST' && $path === '/__/idempotency-replay') {
        $req = json_decode($rawBody, true) ?: [];
        $key = (string)($req['key'] ?? '');
        if ($key === '') {
            respond(400, ['error' => 'key required']);
        }
        withLock(REPLAY_KEYS_FILE, function (array $st) use ($key) {
            $st[$key] = true;
            return $st;
        });
        respond(200, ['status' => 'replay_armed']);
    }

    if ($method === 'POST' && preg_match('#^/api/v1/integrations/inbox/([^/]+)$#', $path, $m)) {
        handleInbox($m[1], $headers, $rawBody);
    }
    if ($method === 'POST' && $path === '/api/v1/integrations/files/request-upload') {
        handleRequestUpload($headers, $rawBody);
    }
    if ($method === 'POST' && preg_match('#^/api/v1/integrations/files/([^/]+)/confirm$#', $path, $m)) {
        handleConfirm($m[1], $headers);
    }
    if ($method === 'POST' && preg_match('#^/fake-s3/([^/]+)$#', $path, $m)) {
        handleS3Upload($m[1]);
    }

    respond(404, ['error' => 'route not found', 'path' => $path]);
} catch (\Throwable $e) {
    respond(500, ['error' => 'fake-server bug', 'message' => $e->getMessage()]);
}

// ====== handlers ======

function handleInbox(string $integrationId, array $headers, string $body): void
{
    if (consumeScenario('inbox', $status, $errorCode)) {
        respond($status, ['error' => ['code' => $errorCode ?? 'SIMULATED', 'message' => 'simulated by /__/scenario']]);
    }

    foreach (['x-integration-timestamp', 'x-integration-signature', 'idempotency-key'] as $h) {
        if (empty($headers[$h])) {
            respond(401, ['error' => ['code' => 'MISSING_HEADERS', 'header' => $h]]);
        }
    }

    $ts = $headers['x-integration-timestamp'];
    $sig = $headers['x-integration-signature'];
    $key = $headers['idempotency-key'];

    // Replay window ±5 min
    $tsTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.v\Z', $ts);
    if (!$tsTime) {
        respond(401, ['error' => ['code' => 'INVALID_TIMESTAMP']]);
    }
    $drift = abs(time() - $tsTime->getTimestamp());
    if ($drift > 300) {
        respond(401, ['error' => ['code' => 'REPLAY_WINDOW_EXCEEDED', 'driftSec' => $drift]]);
    }

    // Signature
    if (!str_starts_with($sig, 'sha256=')) {
        respond(401, ['error' => ['code' => 'INVALID_SIGNATURE_FORMAT']]);
    }
    $expected = 'sha256=' . hash_hmac('sha256', $ts . '.' . $body, HMAC_SECRET);
    if (!hash_equals($expected, $sig)) {
        respond(401, ['error' => ['code' => 'INVALID_SIGNATURE']]);
    }

    // Idempotency replay armed by /__/idempotency-replay
    $replayKeys = json_decode((string)file_get_contents(REPLAY_KEYS_FILE), true) ?: [];
    if (!empty($replayKeys[$key])) {
        respond(200, ['recordId' => 'rec_' . substr(md5($key), 0, 24), 'status' => 'processed', 'replay' => true]);
    }

    respond(202, ['eventId' => $key, 'status' => 'queued']);
}

function handleRequestUpload(array $headers, string $body): void
{
    if (consumeScenario('request-upload', $status, $errorCode)) {
        respond($status, ['error' => ['code' => $errorCode ?? 'SIMULATED']]);
    }
    if (empty($headers['authorization'])) {
        respond(401, ['error' => ['code' => 'MISSING_AUTH']]);
    }
    $req = json_decode($body, true) ?: [];
    $fileId = 'fil_' . bin2hex(random_bytes(8));
    $uploadKey = bin2hex(random_bytes(8));

    // Build a "presigned URL" pointing back to this same server.
    // Browser/S3 in production would be storage.yandexcloud.net; tests don't care.
    $uploadUrl = sprintf('http://%s/fake-s3/%s', $_SERVER['HTTP_HOST'] ?? 'fake-stankoff', $uploadKey);

    respond(200, [
        'data' => [
            'file' => ['id' => $fileId, 'status' => 'pending'],
            'uploadUrl' => $uploadUrl,
            'uploadFields' => [
                'key' => 'uploads/' . $fileId,
                'policy' => 'fake-policy',
                'x-amz-signature' => 'fake-sig',
                // The Content-Type field is what S3 enforces against the presigned policy.
                // We mirror what Stankoff's real partner doc Q3.3 says — the field must
                // match the multipart "file" part's Content-Type.
                'Content-Type' => (string)($req['contentType'] ?? 'application/octet-stream'),
            ],
        ],
    ]);
}

function handleS3Upload(string $uploadKey): void
{
    if (consumeScenario('s3', $status, $errorCode)) {
        respond($status, ['error' => 'simulated']);
    }
    // The body we received already has the multipart parts; we just acknowledge.
    respond(204, null);
}

function handleConfirm(string $fileId, array $headers): void
{
    if (consumeScenario('confirm', $status, $errorCode)) {
        respond($status, ['error' => ['code' => $errorCode ?? 'SIMULATED']]);
    }
    if (empty($headers['authorization'])) {
        respond(401, ['error' => ['code' => 'MISSING_AUTH']]);
    }
    respond(200, ['data' => ['file' => ['id' => $fileId, 'status' => 'ready']]]);
}

// ====== helpers ======

function collectHeaders(): array
{
    $out = [];
    foreach ($_SERVER as $k => $v) {
        if (str_starts_with($k, 'HTTP_')) {
            $out[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
        }
    }
    if (!empty($_SERVER['CONTENT_TYPE'])) {
        $out['content-type'] = $_SERVER['CONTENT_TYPE'];
    }
    return $out;
}

function logRequest(string $method, string $path, array $headers, string $body): void
{
    if (str_starts_with($path, '/__/')) {
        return; // don't log harness calls
    }
    withLock(REQUESTS_FILE, function (array $arr) use ($method, $path, $headers, $body) {
        $arr[] = [
            'time' => microtime(true),
            'method' => $method,
            'path' => $path,
            'headers' => $headers,
            'body' => mb_strlen($body) > 200_000 ? mb_substr($body, 0, 200_000) . '...[trunc]' : $body,
            'bodyLen' => strlen($body),
        ];
        return $arr;
    });
}

/** @return bool true if a scenario consumed; out params filled */
function consumeScenario(string $endpoint, ?int &$outStatus = null, ?string &$outErrorCode = null): bool
{
    $consumed = false;
    withLock(SCENARIOS_FILE, function (array $st) use ($endpoint, &$consumed, &$outStatus, &$outErrorCode) {
        if (isset($st[$endpoint]) && $st[$endpoint]['count'] > 0) {
            $outStatus = $st[$endpoint]['status'];
            $outErrorCode = $st[$endpoint]['errorCode'];
            $st[$endpoint]['count']--;
            if ($st[$endpoint]['count'] <= 0) {
                unset($st[$endpoint]);
            }
            $consumed = true;
        }
        return $st;
    });
    return $consumed;
}

function withLock(string $path, callable $mutator): void
{
    $fp = fopen($path, 'c+');
    flock($fp, LOCK_EX);
    $cur = stream_get_contents($fp);
    $arr = $cur ? (json_decode($cur, true) ?: []) : [];
    $next = $mutator($arr);
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($next, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

#[\NoReturn]
function respond(int $status, mixed $body): void
{
    http_response_code($status);
    if ($body !== null) {
        header('Content-Type: application/json');
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    exit;
}
