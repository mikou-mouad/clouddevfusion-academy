<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensure workflow document tables exist and FKs point to intranet_users (not site users).
 */
final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure workflow tables exist and admin FKs reference intranet_users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS session_documents_generic (
    id BIGSERIAL NOT NULL,
    formation_id VARCHAR(191) NOT NULL,
    session_id VARCHAR(191) DEFAULT NULL,
    category VARCHAR(40) NOT NULL,
    document_type VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    created_by_admin_id INT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_documents (
    id BIGSERIAL NOT NULL,
    student_id BIGINT NOT NULL,
    formation_id VARCHAR(191) NOT NULL,
    session_id VARCHAR(191) DEFAULT NULL,
    category VARCHAR(40) NOT NULL,
    document_type VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'admin',
    signature_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    signed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    signed_by_user_id INT DEFAULT NULL,
    created_by_admin_id INT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS session_validation_tests (
    id BIGSERIAL NOT NULL,
    formation_id VARCHAR(191) NOT NULL,
    session_id VARCHAR(191) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    external_link VARCHAR(500) DEFAULT NULL,
    max_score NUMERIC(6,2) NOT NULL DEFAULT 100,
    created_by_admin_id INT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_validation_results (
    id BIGSERIAL NOT NULL,
    validation_test_id BIGINT NOT NULL,
    student_id BIGINT NOT NULL,
    score NUMERIC(6,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    scored_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    scored_by_admin_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
DO $$
DECLARE
    users_table text;
    r record;
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'intranet_users'
    ) THEN
        users_table := 'intranet_users';
    ELSE
        users_table := 'users';
    END IF;

    FOR r IN
        SELECT c.conname AS constraint_name, t.relname AS table_name
        FROM pg_constraint c
        JOIN pg_class t ON t.oid = c.conrelid
        WHERE c.contype = 'f'
          AND t.relname IN (
              'session_documents_generic',
              'student_documents',
              'session_validation_tests',
              'student_validation_results'
          )
          AND pg_get_constraintdef(c.oid) LIKE '%REFERENCES users(%'
    LOOP
        EXECUTE format('ALTER TABLE %I DROP CONSTRAINT %I', r.table_name, r.constraint_name);
    END LOOP;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_intr_doc_generic_admin') THEN
        EXECUTE format(
            'ALTER TABLE session_documents_generic ADD CONSTRAINT fk_intr_doc_generic_admin FOREIGN KEY (created_by_admin_id) REFERENCES %I (id) ON DELETE SET NULL',
            users_table
        );
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_intr_student_doc_signed_user') THEN
        EXECUTE format(
            'ALTER TABLE student_documents ADD CONSTRAINT fk_intr_student_doc_signed_user FOREIGN KEY (signed_by_user_id) REFERENCES %I (id) ON DELETE SET NULL',
            users_table
        );
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_intr_student_doc_admin') THEN
        EXECUTE format(
            'ALTER TABLE student_documents ADD CONSTRAINT fk_intr_student_doc_admin FOREIGN KEY (created_by_admin_id) REFERENCES %I (id) ON DELETE SET NULL',
            users_table
        );
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_intr_validation_admin') THEN
        EXECUTE format(
            'ALTER TABLE session_validation_tests ADD CONSTRAINT fk_intr_validation_admin FOREIGN KEY (created_by_admin_id) REFERENCES %I (id) ON DELETE SET NULL',
            users_table
        );
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_intr_validation_result_admin') THEN
        EXECUTE format(
            'ALTER TABLE student_validation_results ADD CONSTRAINT fk_intr_validation_result_admin FOREIGN KEY (scored_by_admin_id) REFERENCES %I (id) ON DELETE SET NULL',
            users_table
        );
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        // Non-destructive migration: no down.
    }
}
