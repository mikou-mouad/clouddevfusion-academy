# Intranet database setup (Option 1)

This backend now uses a dedicated PostgreSQL database for the intranet.

## 1) Create the dedicated database on the same PostgreSQL server

```sql
CREATE DATABASE intranet_db;
```

## 2) Configure local connection

Edit `.env.local`:

```dotenv
DATABASE_URL="postgresql://app:ChangeMeNow@127.0.0.1:5432/intranet_db?serverVersion=16&charset=utf8"
```

## 3) Run migration

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## 4) Seed default admin

```bash
php bin/console app:intranet:seed-admin admin@clouddev.local admin123
```

## 5) Verify

```bash
php bin/console doctrine:query:sql "SELECT id, email, created_at FROM users;"
```

## 6) Import existing intranet JSON state (optional)

If you already have data in `var/intranet-admin-state.json`, import it into SQL tables:

```bash
php bin/console app:intranet:import-state
```

To force a fresh re-import (truncate intranet domain tables first):

```bash
php bin/console app:intranet:import-state --truncate
```

Import a production backup file:

```bash
php bin/console app:intranet:import-state --file=var/intranet-admin-state.PROD.json --truncate
```

The import also creates/updates `users` accounts (hashed passwords) for students and trainers found in the JSON.

## 7) Migrate local PostgreSQL to production (recommended)

This copies the real SQL tables (users, formations, students, trainers, etc.).

### Step A — dump local database

```bash
cd intranet/backend
chmod +x scripts/dump-local-db.sh scripts/push-db-to-prod.sh
./scripts/dump-local-db.sh
```

Creates `var/intranet_db_dump.sql.gz` (~90 KB).

### Step B — upload + import on O2Switch

Option 1 — one command (needs FTP secrets):

```bash
export FTP_HOST=...
export FTP_PORT=21
export FTP_USER=...
export FTP_PASS=...
export INTRANET_BACKEND_REMOTE=...
export APP_SECRET=...
./scripts/push-db-to-prod.sh
```

Option 2 — manual:

1. Upload `var/intranet_db_dump.sql.gz` via FTP to `intranet/backend/var/` on the server.
2. Deploy backend code (includes `public/import-database.php`).
3. Open once in browser or curl:

```bash
curl -G "https://academy.clouddevfusion.com/intranet/backend/public/import-database.php" \
  --data-urlencode "token=YOUR_APP_SECRET"
```

Option 3 — SSH on server if available:

```bash
php bin/console app:intranet:import-database
```

### Notes

- The dump uses `--clean --if-exists`: production intranet tables are replaced.
- Upload `public/uploads/` too if documents/PDFs must work.
- Do not commit `var/intranet_db_dump.sql.gz` (contains passwords).

## 8) Production import from JSON (alternative)

1. Upload `intranet-admin-state.PROD.json` to `var/` on the server (do not commit this file).
2. Upload `public/uploads/` assets if resources use local files.
3. Run:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:intranet:import-state --file=var/intranet-admin-state.PROD.json --truncate
php bin/console cache:clear --env=prod
```
