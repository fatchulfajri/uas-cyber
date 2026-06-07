# Build the per-challenge Docker images that spawn.php launches on demand.
# Windows convenience wrapper for build-challenges.sh. Run from a shell with the
# `docker` CLI available. Image tags MUST match web/src/challenge_map.php.
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

Write-Host '==> Building ctf-reverse'
docker build -t ctf-reverse ./challenge/reverse

Write-Host '==> Building ctf-crypto'
docker build -t ctf-crypto ./challenge/crypto

Write-Host '==> Building ctf-network'
docker build -t ctf-network ./challenge/network

Write-Host '==> Done. Images:'
docker images | Select-String '^ctf-'
