#!/usr/bin/env bash
# Copies the AG-UI 1.0 specification (JSON Schema, per-type fixtures, conformance
# streams) and the cross-SDK fixtures from the ag-ui repository, frozen at one commit.
# Updating them is deliberate: run this with a commit sha, review the diff, regenerate
# the model (php tools/generate-model.php), and commit everything together.
set -euo pipefail

SHA="${1:?usage: tools/sync-spec.sh <ag-ui commit sha>}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

curl -sL "https://codeload.github.com/ag-ui-protocol/ag-ui/tar.gz/$SHA" | tar -xz -C "$TMP"
SRC="$(echo "$TMP"/ag-ui-*)"

rm -rf "$ROOT/resources/ag-ui/spec-1.0" "$ROOT/resources/ag-ui/sdk-fixtures"
cp -R "$SRC/spec/1.0" "$ROOT/resources/ag-ui/spec-1.0"
cp -R "$SRC/sdks/fixtures" "$ROOT/resources/ag-ui/sdk-fixtures"
echo "$SHA" > "$ROOT/resources/ag-ui/COMMIT"
echo "Synced AG-UI spec at $SHA."
