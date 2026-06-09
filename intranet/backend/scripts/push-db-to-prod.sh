#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DUMP="${ROOT_DIR}/var/intranet_db_dump.sql.gz"
PROD_IMPORT_URL="${PROD_IMPORT_URL:-https://academy.clouddevfusion.com/intranet/backend/public/import-database.php}"

: "${FTP_HOST:?Set FTP_HOST}"
: "${FTP_PORT:?Set FTP_PORT}"
: "${FTP_USER:?Set FTP_USER}"
: "${FTP_PASS:?Set FTP_PASS}"
: "${INTRANET_BACKEND_REMOTE:?Set INTRANET_BACKEND_REMOTE}"
: "${APP_SECRET:?Set APP_SECRET}"

if [[ ! -f "${DUMP}" ]]; then
  "${ROOT_DIR}/scripts/dump-local-db.sh"
fi

echo "Uploading dump to production..."
lftp -c "
set ftp:ssl-allow no
set ftp:passive-mode yes
set net:timeout 120
set net:max-retries 3
set cmd:fail-exit no
open -p ${FTP_PORT} -u '${FTP_USER}','${FTP_PASS}' ${FTP_HOST}
cd ${INTRANET_BACKEND_REMOTE}
mkdir -f var
put ${DUMP} -o var/intranet_db_dump.sql.gz
bye
"

echo "Triggering import on production..."
response="$(curl -sS -G "${PROD_IMPORT_URL}" --data-urlencode "token=${APP_SECRET}")"
echo "${response}"

if ! echo "${response}" | grep -q 'PostgreSQL import OK'; then
  echo "Import failed." >&2
  exit 1
fi

echo "Local PostgreSQL data migrated to production."
