#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
archive="${1:-$root/dist/acorn-safety-healthcheck-1.0.2.zip}"
smoke="$root/.release-smoke"
wp_env="$root/node_modules/.bin/wp-env"
base_url="http://localhost:8890"

[[ -s "$archive" ]] || { echo "Release ZIP not found: $archive" >&2; exit 1; }
[[ -x "$wp_env" ]] || { echo "wp-env binary not found. Run npm ci first." >&2; exit 1; }

rm -rf "$smoke"
mkdir -p "$smoke"
unzip -q "$archive" -d "$smoke"

cat > "$smoke/.wp-env.json" <<'JSON'
{
  "core": null,
  "plugins": ["./acorn-safety-healthcheck"],
  "port": 8890,
  "testsPort": 8891,
  "config": {
    "WP_DEBUG": true,
    "WP_DEBUG_LOG": true,
    "WP_DEBUG_DISPLAY": false
  }
}
JSON

cleanup() {
  if [[ -d "$smoke" ]]; then
    (
      cd "$smoke"
      "$wp_env" stop >/dev/null 2>&1 || true
    )
    rm -rf "$smoke"
  fi
}
trap cleanup EXIT

cd "$smoke"
"$wp_env" start

"$wp_env" run cli wp plugin is-active acorn-safety-healthcheck
"$wp_env" run cli wp option update permalink_structure '/%postname%/'
"$wp_env" run cli wp post create   --post_type=page   --post_status=publish   --post_name=health-and-safety-healthcheck   --post_title='Health and Safety Healthcheck'   --post_content='[acorn_safety_healthcheck]' >/dev/null
"$wp_env" run cli wp rewrite flush --hard >/dev/null

landing="$(curl -fsS "$base_url/health-and-safety-healthcheck/")"
grep -q 'id="acorn-healthcheck"' <<<"$landing"

start="$(curl -fsS -X POST "$base_url/wp-json/acorn-healthcheck/v1/assessments")"
token="$(jq -r '.token // empty' <<<"$start")"
[[ -n "$token" ]] || { echo "Assessment start did not return a token." >&2; exit 1; }

profile='{
  "jurisdiction":"england",
  "employee_band":"1_4",
  "sector":"office_professional",
  "workplace_types":["office"],
  "premises_responsibility":"yes",
  "shared_premises":"yes",
  "risk_flags":["dse"],
  "water_system_responsibility":"no",
  "maintenance_repair_responsibility":"no",
  "building_pre_2000":"not_relevant",
  "intrusive_work_planned":"no"
}'

state="$(curl -fsS -X PATCH   -H 'Content-Type: application/json'   --data "$profile"   "$base_url/wp-json/acorn-healthcheck/v1/assessments/$token/profile")"

mapfile -t question_keys < <(jq -r '.questions[].key' <<<"$state")
[[ "${#question_keys[@]}" -gt 0 ]] || { echo "No applicable questions returned." >&2; exit 1; }

for key in "${question_keys[@]}"; do
  curl -fsS -X PUT     -H 'Content-Type: application/json'     --data '{"answer":"yes"}'     "$base_url/wp-json/acorn-healthcheck/v1/assessments/$token/answers/$key" >/dev/null
done

assessed="$(curl -fsS -X POST "$base_url/wp-json/acorn-healthcheck/v1/assessments/$token/assess")"
[[ "$(jq -r '.status' <<<"$assessed")" == "assessed" ]] || {
  echo "Assessment did not reach assessed state." >&2
  exit 1
}

completed="$(curl -fsS -X POST   -H 'Content-Type: application/json'   --data '{
    "first_name":"Release",
    "last_name":"Smoke",
    "company":"Acorn Release Smoke",
    "email":"release-smoke@example.test",
    "audit_requested":false,
    "marketing_consent":false
  }'   "$base_url/wp-json/acorn-healthcheck/v1/assessments/$token/complete")"

report_url="$(jq -r '.report_url // empty' <<<"$completed")"
[[ -n "$report_url" ]] || { echo "Completion did not return a report URL." >&2; exit 1; }

report="$(curl -fsS "$report_url")"
grep -q 'Your Health &amp; Safety Healthcheck' <<<"$report"
grep -q 'Resend email' <<<"$report"

"$wp_env" run cli bash -lc '
  log=/var/www/html/wp-content/debug.log
  if [ -f "$log" ] && grep -Eiq "PHP (Warning|Notice|Fatal error|Parse error)" "$log"; then
    echo "PHP warning/notice/fatal found in clean release smoke test:" >&2
    grep -Ei "PHP (Warning|Notice|Fatal error|Parse error)" "$log" >&2
    exit 1
  fi
'

echo "Clean release ZIP smoke test passed."
