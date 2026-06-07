<?php
/**
 * spawn.php — AJAX endpoint that launches an ephemeral, isolated challenge
 * container for the logged-in student and returns its access URL.
 *
 * Contract (JSON):
 *   POST challenge_id=<int>
 *   200 {"ok":true,  "url":"http://host:port", "port":N, "expires_at":"..."}
 *   xxx {"ok":false, "error":"...", "spawnable":bool}
 *
 * Security: image is resolved ONLY via the server-side allowlist
 * (challenge_map.php); no client value ever reaches the shell unescaped.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../config/challenge_map.php';
require_once __DIR__ . '/../lib/docker.php';

header('Content-Type: application/json');

/**
 * Emit a JSON error response and terminate the request.
 *
 * @param {string} $msg Human-readable error message for the client.
 * @param {bool} $spawnable Whether this challenge is container-backed; false tells the frontend to fall back to the direct flag-submit modal.
 * @param {int} $http HTTP status code to send.
 * @returns {void}
 */
function fail(string $msg, bool $spawnable = true, int $http = 400): void
{
    http_response_code($http);
    echo json_encode(['ok' => false, 'error' => $msg, 'spawnable' => $spawnable]);
    exit;
}

// --- Auth: only logged-in students may spawn ---------------------------------
if (!isset($_SESSION['user'])) {
    fail('Akses ditolak: silakan login terlebih dahulu.', true, 401);
}
$user = $_SESSION['user'];
if (($user['role'] ?? '') !== 'mahasiswa') {
    fail('Hanya mahasiswa yang dapat menjalankan challenge.', true, 403);
}
$user_id = (int) $user['id'];

// --- Validate challenge_id against the allowlist -----------------------------
$challenge_id = (int) ($_POST['challenge_id'] ?? 0);
if (!isset($CHALLENGE_IMAGES[$challenge_id])) {
    // Not a container-backed challenge (e.g. web SQLi/XSS) -> frontend falls
    // back to the direct flag-submit modal.
    fail('Challenge ini tidak memerlukan instance container.', false, 422);
}
$cfg   = $CHALLENGE_IMAGES[$challenge_id];
$image = $cfg['image'];       // allowlisted constant, safe
$cport = (int) $cfg['port'];
$ttl   = (int) $cfg['ttl'];

// --- Reclaim anything past its TTL before doing more work --------------------
sweep_expired($conn);

// --- Idempotent: reuse an existing live instance -----------------------------
$stmt = $conn->prepare(
    "SELECT * FROM active_instances
     WHERE user_id = ? AND challenge_id = ? AND status = 'running' AND expires_at > NOW()"
);
$stmt->bind_param('ii', $user_id, $challenge_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
if ($existing) {
    echo json_encode([
        'ok'            => true,
        'reused'        => true,
        'url'           => 'http://' . instance_host() . ':' . (int) $existing['port'],
        'port'          => (int) $existing['port'],
        'expires_at'    => $existing['expires_at'],
        'ttl_remaining' => max(0, strtotime($existing['expires_at']) - time()),
    ]);
    exit;
}

// --- Launch a fresh container ------------------------------------------------
$name = sprintf('ctf_%d_%d_%s', $user_id, $challenge_id, uniqid());
// $name is built from ints + uniqid() (hex) so it is shell-safe, but escape
// defensively anyway. $image is an allowlisted constant.
$run_args = sprintf(
    'run -d --rm --name %s -P --memory=256m --cpus=0.5 --pids-limit=128 --label ctf=1 %s',
    escapeshellarg($name),
    escapeshellarg($image)
);
$run = ctf_docker($run_args);
if (!$run['ok'] || !valid_cid($run['output'])) {
    fail('Gagal menjalankan container: ' . $run['output'], true, 500);
}
$container_id = $run['output'];

// --- Resolve the host port Docker assigned to the ttyd port ------------------
$port_res = ctf_docker('port ' . escapeshellarg($name) . ' ' . $cport . '/tcp');
// Output looks like "0.0.0.0:49153" (and possibly an IPv6 line). Take the last
// colon-separated field of the first line.
$host_port = 0;
if ($port_res['ok']) {
    $first = strtok($port_res['output'], "\n");
    if ($first !== false) {
        $host_port = (int) substr(strrchr($first, ':'), 1);
    }
}
if ($host_port <= 0) {
    if (valid_cid($container_id)) {
        ctf_docker_async('kill ' . escapeshellarg($container_id));
    }
    fail('Container berjalan tetapi port tidak dapat dipetakan.', true, 500);
}

// --- Persist the instance ----------------------------------------------------
$stmt = $conn->prepare(
    "INSERT INTO active_instances
        (user_id, challenge_id, container_id, container_name, port, status, expires_at)
     VALUES (?, ?, ?, ?, ?, 'running', DATE_ADD(NOW(), INTERVAL ? SECOND))"
);
$stmt->bind_param('iissii', $user_id, $challenge_id, $container_id, $name, $host_port, $ttl);
if (!$stmt->execute()) {
    if (valid_cid($container_id)) {
        ctf_docker_async('kill ' . escapeshellarg($container_id));
    }
    fail('Gagal menyimpan instance: ' . $conn->error, true, 500);
}

$expires_at = $conn->query("SELECT DATE_ADD(NOW(), INTERVAL $ttl SECOND) AS e")
    ->fetch_assoc()['e'];

echo json_encode([
    'ok'            => true,
    'reused'        => false,
    'url'           => 'http://' . instance_host() . ':' . $host_port,
    'port'          => $host_port,
    'expires_at'    => $expires_at,
    'ttl_remaining' => $ttl,
]);

/**
 * Resolve the hostname or IP that students use to reach spawned instances.
 * Prefers the CTF_INSTANCE_HOST env var, otherwise derives it from the current
 * request host (with any port stripped) so it works over localhost or a LAN IP.
 *
 * @returns {string} Bare hostname or IP without a port.
 */
function instance_host(): string
{
    $env = getenv('CTF_INSTANCE_HOST');
    if ($env) {
        return $env;
    }
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');
    $host = preg_replace('/:\d+$/', '', $host);
    return $host ?: 'localhost';
}
