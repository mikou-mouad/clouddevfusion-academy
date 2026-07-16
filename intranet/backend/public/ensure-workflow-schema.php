<?php
/**
 * Ensure workflow document tables exist on production (bypasses Doctrine schema introspection issues).
 * Usage: /intranet/backend/public/ensure-workflow-schema.php?token=APP_SECRET
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$autoload = __DIR__.'/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    echo "Vendor not ready\n";
    exit(1);
}

require_once $autoload;

use Symfony\Component\Dotenv\Dotenv;

if (is_file(__DIR__.'/../.env')) {
    (new Dotenv())->bootEnv(__DIR__.'/../.env');
}

$expectedToken = (string) ($_ENV['APP_SECRET'] ?? '');
$providedToken = (string) ($_GET['token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit(1);
}

set_time_limit(300);

$version = 'DoctrineMigrations\\Version20260525120000';

$databaseUrl = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
$parts = parse_url($databaseUrl);
if (!\is_array($parts)) {
    http_response_code(500);
    echo "Invalid DATABASE_URL\n";
    exit(1);
}

$workflowSql = <<<'SQL'
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
);

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
);

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
);

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
);

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
SQL;

try {
    $pdo = new PDO(
        sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $parts['host'] ?? '127.0.0.1',
            (int) ($parts['port'] ?? 5432),
            ltrim((string) ($parts['path'] ?? ''), '/')
        ),
        urldecode((string) ($parts['user'] ?? '')),
        urldecode((string) ($parts['pass'] ?? ''))
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $already = $pdo->prepare('SELECT 1 FROM doctrine_migration_versions WHERE version = :v');
    $already->execute(['v' => $version]);
    if ($already->fetchColumn() === false) {
        $pdo->exec($workflowSql);
        $pdo->prepare(
            'INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (:v, NOW(), 0)'
        )->execute(['v' => $version]);
        echo "Workflow tables ensured.\n";
    } else {
        echo "Workflow schema migration already applied.\n";
    }

    foreach ([
        'session_documents_generic',
        'student_documents',
        'session_validation_tests',
        'student_validation_results',
    ] as $table) {
        $pdo->query(sprintf('SELECT 1 FROM %s LIMIT 1', $table));
        echo "OK: {$table}\n";
    }

    echo "Workflow document upload is ready.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}
