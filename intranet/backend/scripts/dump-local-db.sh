#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT="${ROOT_DIR}/var/intranet_db_dump.sql.gz"
DATABASE_URL="${DATABASE_URL:-postgresql://app:ChangeMeNow@127.0.0.1:5432/intranet_db}"

mkdir -p "${ROOT_DIR}/var"

echo "Dumping local intranet_db → ${OUTPUT}"
pg_dump "${DATABASE_URL}" \
  --no-owner \
  --no-acl \
  --clean \
  --if-exists \
  | gzip -9 > "${OUTPUT}"

ls -lh "${OUTPUT}"
echo "Done. Upload this file to prod: var/intranet_db_dump.sql.gz"
