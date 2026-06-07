<?php
/**
 * Server-side challenge -> Docker image allowlist.
 *
 * This is the security boundary for container spawning: the frontend only ever
 * sends a numeric challenge_id, which is cast to int and used as the key here.
 * The image name is NEVER taken from client input. A challenge_id that is not a
 * key in this map is simply not spawnable.
 *
 * Challenge IDs follow the INSERT order in db/init.sql:
 *   1 = Web SQL Injection   (web-app challenge, no container)
 *   2 = Reverse Engineering  -> ctf-reverse
 *   3 = Web XSS Attack       (web-app challenge, no container)
 *   4 = Cryptography         -> ctf-crypto
 *   5 = Network PCAP         -> ctf-network
 *
 * 'port' is the container's internal port that ttyd listens on (see each
 * challenge Dockerfile CMD). 'ttl' is the instance lifetime in seconds; it must
 * match the `timeout` baked into the image so the DB sweep and the container's
 * own self-destruct stay in sync.
 */

$CHALLENGE_IMAGES = [
    2 => ['image' => 'ctf-reverse', 'port' => 7681, 'ttl' => 1800],
    4 => ['image' => 'ctf-crypto',  'port' => 7681, 'ttl' => 1800],
    5 => ['image' => 'ctf-network', 'port' => 7681, 'ttl' => 1800],
];

/**
 * Non-containerized web challenges: static vulnerable pages served directly from
 * public/. Maps challenge_id -> URL relative to the web root, used by the
 * dashboard's "Open Challenge" button. A challenge here is never spawnable.
 */
$WEB_CHALLENGES = [
    1 => 'challenges/sqli/index.php',
    3 => 'challenges/xss/index.php',
];
