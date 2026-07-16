<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Separate intranet accounts (intranet_users) from main site admins (users).
 * Non-destructive: RENAME only (no DROP/TRUNCATE), site users created only if missing.
 */
final class Version20260524120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename intranet users table to intranet_users and restore site users table (no data loss).';
    }

    public function up(Schema $schema): void
    {
        // 1) Rename legacy intranet table — keeps every row and FK intact (PostgreSQL updates FK targets).
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'role_id'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'intranet_users'
    ) THEN
        ALTER TABLE users RENAME TO intranet_users;
        IF EXISTS (SELECT 1 FROM pg_class WHERE relname = 'users_id_seq') THEN
            ALTER SEQUENCE users_id_seq RENAME TO intranet_users_id_seq;
        END IF;
    END IF;
END $$;
SQL);

        // 2) Create site users table only when it does not exist yet (never overwrites existing site admins).
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'users'
    ) THEN
        CREATE SEQUENCE IF NOT EXISTS users_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
        CREATE TABLE users (
            id INT NOT NULL DEFAULT nextval('users_id_seq'),
            email VARCHAR(180) NOT NULL,
            roles TEXT NOT NULL,
            password VARCHAR(255) NOT NULL,
            username VARCHAR(100) NOT NULL,
            active BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id),
            UNIQUE(email)
        );
        CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_SITE_USERS_EMAIL ON users (email);
        COMMENT ON COLUMN users.roles IS '(DC2Type:json)';
        COMMENT ON COLUMN users.created_at IS '(DC2Type:datetime_immutable)';
        COMMENT ON COLUMN users.updated_at IS '(DC2Type:datetime_immutable)';
    END IF;
END $$;
SQL);

        // 3) Seed default site admins only when those emails are absent (never updates existing passwords).
        $adminPassword = '$2y$12$rcTi/.dppi/umYBbJTMdBuiGT0NENg.cMaH/aIoIU9wCri4Fkli1y';
        $superAdminPassword = '$2y$12$qRGOAn7QgBnem40Z1BGrxeN7FoOg1inQyMMo1MzKoF0mzKi0UbOve';

        $this->addSql(
            "INSERT INTO users (id, email, roles, password, username, active, created_at)
             SELECT 1, 'admin@clouddevfusion.com', '[\"ROLE_ADMIN\"]', :admin_password, 'Admin', true, NOW()
             WHERE EXISTS (
                 SELECT 1 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
             )
             AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@clouddevfusion.com')",
            ['admin_password' => $adminPassword],
        );
        $this->addSql(
            "INSERT INTO users (id, email, roles, password, username, active, created_at)
             SELECT 2, 'superadmin@clouddevfusion.com', '[\"ROLE_SUPER_ADMIN\"]', :super_admin_password, 'Super Admin', true, NOW()
             WHERE EXISTS (
                 SELECT 1 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
             )
             AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'superadmin@clouddevfusion.com')",
            ['super_admin_password' => $superAdminPassword],
        );
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
    ) THEN
        PERFORM setval(
            'users_id_seq',
            GREATEST((SELECT COALESCE(MAX(id), 0) FROM users), 1)
        );
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'intranet_users'
    ) THEN
        DROP TABLE users;
        DROP SEQUENCE IF EXISTS users_id_seq;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'intranet_users'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'users'
    ) THEN
        ALTER TABLE intranet_users RENAME TO users;
        IF EXISTS (SELECT 1 FROM pg_class WHERE relname = 'intranet_users_id_seq') THEN
            ALTER SEQUENCE intranet_users_id_seq RENAME TO users_id_seq;
        END IF;
    END IF;
END $$;
SQL);
    }
}
