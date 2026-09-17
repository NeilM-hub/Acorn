#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"; build="$(mktemp -d)"; trap 'rm -rf "$build"' EXIT
mkdir -p "$build/acorn-safety-healthcheck" "$root/dist"
rsync -a --exclude='.git' --exclude='.wp-env.json' --exclude='tests' --exclude='node_modules' --exclude='docs' --exclude='dist' --exclude='*.map' "$root/" "$build/acorn-safety-healthcheck/"
composer install --working-dir="$build/acorn-safety-healthcheck" --no-dev --optimize-autoloader --no-interaction
(cd "$build" && zip -qr "$root/dist/acorn-safety-healthcheck-1.0.0.zip" acorn-safety-healthcheck)
