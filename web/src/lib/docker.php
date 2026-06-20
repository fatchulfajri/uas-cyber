<?php

/**
 * Validate a Docker container ID before it is interpolated into a shell command.
 * Acts as the last guard against command injection: IDs always originate from
 * the database, never directly from client input.
 *
 * @param {string} $id Candidate container ID or hash.
 * @returns {bool} True when the ID is lowercase hex and 12 to 64 chars long.
 */
function valid_cid(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{12,64}$/', $id);
}

/**
 * Execute a single Docker CLI subcommand and capture its result. This is the one
 * choke point for all Docker calls; callers must escapeshellarg() any dynamic
 * arguments before passing them in.
 *
 * @param {string} $args Arguments following the "docker" executable.
 * @returns {array} Result map: ok (bool), output (string), code (int).
 */
function ctf_docker(string $args): array
{
    $cmd = 'docker ' . $args . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    return [
        'ok'     => $code === 0,
        'output' => trim(implode("\n", $output)),
        'code'   => $code,
    ];
}

/**
 * Check whether a Docker container is currently running. Queries the Docker
 * daemon with `docker ps -q` filtered by container ID. Returns false when the
 * container has crashed, been OOM-killed, or was removed entirely.
 *
 * @param {string} $cid Full or short container ID (hex, 12-64 chars).
 * @returns {bool} True only when Docker reports the container as running.
 */
function is_container_alive(string $cid): bool
{
    if (!valid_cid($cid)) {
        return false;
    }
    $res = ctf_docker('ps -q -f id=' . escapeshellarg($cid));
    return $res['ok'] && $res['output'] !== '';
}

/**
 * Dispatch a Docker subcommand in the background and return immediately without
 * waiting for it to finish. Stdio is fully redirected and the process is
 * detached with nohup so it survives the request, keeping slow operations such
 * as stopping a container off the response path. Dynamic arguments must be
 * escapeshellarg()'d by the caller.
 *
 * @param {string} $args Arguments following the "docker" executable.
 * @returns {void}
 */
function ctf_docker_async(string $args): void
{
    exec('nohup docker ' . $args . ' > /dev/null 2>&1 &');
}

/**
 * Mark an instance row as stopped and tear down its container without blocking.
 * The database is updated synchronously so the instance is immediately gone from
 * the application's point of view, while the container is killed in the
 * background (SIGKILL via docker kill; the --rm flag removes it afterwards). The
 * container ID is re-validated before it can reach the shell.
 *
 * @param {mysqli} $conn Active database connection.
 * @param {array} $row Instance row containing at least id and container_id.
 * @returns {void}
 */
function stop_instance(mysqli $conn, array $row): void
{
    $stmt = $conn->prepare("UPDATE active_instances SET status = 'stopped' WHERE id = ?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();

    $cid = $row['container_id'] ?? '';
    if (valid_cid($cid)) {
        ctf_docker_async('kill ' . escapeshellarg($cid));
    }
}

/**
 * Reclaim every running instance whose TTL has elapsed by stopping its container
 * and updating its status. Complements each container's internal self-destruct
 * timeout so resources are freed even if a client never returns.
 *
 * @param {mysqli} $conn Active database connection.
 * @returns {void}
 */
function sweep_expired(mysqli $conn): void
{
    $res = $conn->query(
        "SELECT id, container_id FROM active_instances
         WHERE status = 'running' AND expires_at < NOW()"
    );
    if (!$res) {
        return;
    }
    while ($row = $res->fetch_assoc()) {
        stop_instance($conn, $row);
    }
}
