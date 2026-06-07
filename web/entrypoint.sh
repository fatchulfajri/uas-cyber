#!/bin/sh
# Give the Apache/PHP user (www-data) permission to talk to the mounted Docker
# socket without running the web server as root. We match www-data into whatever
# group owns /var/run/docker.sock on the host.
set -e

SOCK=/var/run/docker.sock
if [ -S "$SOCK" ]; then
    GID="$(stat -c '%g' "$SOCK")"
    GROUP="$(getent group "$GID" | cut -d: -f1)"
    if [ -z "$GROUP" ]; then
        GROUP=dockerhost
        groupadd -g "$GID" "$GROUP" 2>/dev/null || groupadd "$GROUP" 2>/dev/null || true
    fi
    usermod -aG "$GROUP" www-data 2>/dev/null || true
else
    echo "WARNING: $SOCK not found — challenge spawning will not work." >&2
fi

exec "$@"
