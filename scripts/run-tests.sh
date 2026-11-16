#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

vendor/bin/phpunit --testsuite Unit
bash scripts/run-integration-tests.sh
