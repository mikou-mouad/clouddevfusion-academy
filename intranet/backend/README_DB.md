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
