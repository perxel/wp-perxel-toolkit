#!/usr/bin/env bash
#
# Run the official WordPress Plugin Check against the built plugin - the same
# check CI runs (.github/workflows/lint.yml), against the same shippable zip.
# Plugin Check does NOT read phpcs.xml.dist, so keep any --ignore-codes here in
# sync with the `ignore-codes:` block in lint.yml.
#
# Needs wp-cli with the plugin-check package:
#   wp package install wordpress/plugin-check-cli
#
# Usage: bin/plugin-check.sh

set -euo pipefail

SLUG="perxel-example"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

bin/build-zip.sh
rm -rf build && mkdir build
unzip -q "dist/${SLUG}.zip" -d build

# Mirror lint.yml -> ignore-codes. Empty for the base template.
IGNORE=""

ARGS=( "build/${SLUG}" "--slug=${SLUG}" )
[[ -n "$IGNORE" ]] && ARGS+=( "--ignore-codes=${IGNORE}" )

wp plugin check "${ARGS[@]}"
