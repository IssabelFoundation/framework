#!/usr/bin/env bash
# Build working-tree snapshots locally; never pushes or publishes.
set -euo pipefail
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
framework_root="$(cd -- "$script_dir/../.." && pwd)"
assistant_root="${ASSISTANT_ROOT:-$(dirname "$framework_root")/ai_assistant}"
output="${RPM_OUTPUT:-$framework_root/build/local/output}"
local_tag="${LOCAL_RPM_TAG:-$(date -u +%Y%m%d%H%M%S)}"
[[ "$local_tag" =~ ^[a-zA-Z0-9]+$ ]] || { echo 'LOCAL_RPM_TAG must be alphanumeric' >&2; exit 1; }
mkdir -p "$output"
output="$(cd "$output" && pwd)"
docker build --platform linux/amd64 -t issabel-rpm-el8:local "$script_dir"
docker run --rm --platform linux/amd64 \
  -v "$framework_root:/source/framework:ro" \
  -v "$assistant_root:/source/ai_assistant:ro" \
  -v "$output:/output" \
  -e "LOCAL_RPM_TAG=$local_tag" \
  issabel-rpm-el8:local bash /source/framework/build/local/build-in-container.sh
docker run --rm --platform linux/amd64 \
  -v "$script_dir:/tests:ro" -v "$output:/output:ro" \
  issabel-rpm-el8:local bash /tests/test-secrets.sh > "$output/secrets-test.log" 2>&1
cat "$output/secrets-test.log"
