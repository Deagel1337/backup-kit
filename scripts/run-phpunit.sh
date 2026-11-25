#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

docker compose up --detach --wait mariadb-test

database_port="$(docker compose port mariadb-test 3306 | awk -F: '{print $NF}')"
if [[ ! "$database_port" =~ ^[0-9]+$ ]]; then
    echo "Could not determine the published MariaDB test port." >&2
    exit 1
fi

export BACKUP_KIT_TEST_DB_HOST=127.0.0.1
export BACKUP_KIT_TEST_DB_PORT="$database_port"
export BACKUP_KIT_TEST_DB_USERNAME=root
export BACKUP_KIT_TEST_DB_PASSWORD=root

exec "$@"
