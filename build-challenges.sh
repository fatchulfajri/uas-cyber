#!/bin/sh
# Build the per-challenge Docker images that spawn.php launches on demand.
# Run this once before students start challenges (and again after editing any
# challenge source). The image tags MUST match web/src/challenge_map.php.
set -e

cd "$(dirname "$0")"

echo "==> Building ctf-reverse"
docker build -t ctf-reverse ./challenge/reverse

echo "==> Building ctf-crypto"
docker build -t ctf-crypto ./challenge/crypto

echo "==> Building ctf-network"
docker build -t ctf-network ./challenge/network

echo "==> Done. Images:"
docker images | grep '^ctf-' || true
