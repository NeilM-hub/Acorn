#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
build="$(mktemp -d)"
plugin="$build/acorn-safety-healthcheck"
trap 'rm -rf "$build"' EXIT

rm -rf "$root/dist"
mkdir -p "$plugin" "$root/dist"
cp "$root/acorn-safety-healthcheck.php" "$root/composer.json" "$root/README.md" "$plugin/"
for directory in src assets config templates; do
  cp -R "$root/$directory" "$plugin/$directory"
done
if [[ -f "$root/composer.lock" ]]; then cp "$root/composer.lock" "$plugin/"; fi

composer install --working-dir="$plugin" --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
rm -f "$plugin/composer.json" "$plugin/composer.lock"

version="$(php -r '$data=file_get_contents($argv[1]); preg_match("/Version:\\s*([0-9.]+)/",$data,$m); echo $m[1]??"";' "$plugin/acorn-safety-healthcheck.php")"
[[ "$version" == "1.0.2" ]] || { echo "Unexpected plugin version: $version" >&2; exit 1; }
archive="$root/dist/acorn-safety-healthcheck-$version.zip"
(cd "$build" && zip -qr "$archive" acorn-safety-healthcheck)
[[ -s "$archive" ]]
