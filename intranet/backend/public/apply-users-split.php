<?php
/**
 * Apply users/intranet_users split directly (bypasses Doctrine schema introspection on older PG).
 * Usage: /intranet/backend/public/apply-users-split.php?token=APP_SECRET
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

$databaseUrl = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
if ($databaseUrl === '') {
    http_response_code(500);
    echo "DATABASE_URL is not configured.\n";
    exit(1);
}

$version = 'DoctrineMigrations\\Version20260524120000';

try {
    $pdo = createPdo($databaseUrl);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT 1 FROM doctrine_migration_versions WHERE version = :version');
    $stmt->execute(['version' => $version]);
    if ($stmt->fetchColumn() !== false) {
        echo "Migration already applied: {$version}\n";
        printCounts($pdo);
        exit(0);
    }

    echo "Step 1/3: Rename intranet users table (no data loss)...\n";
    $pdo->exec(<<<'SQL'
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

    echo "Step 2/3: Create site users table if missing...\n";
    $pdo->exec(<<<'SQL'
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
    END IF;
END $$;
SQL);

    echo "Step 3/3: Seed default site admins (only if absent)...\n";
    $adminPassword = '$2y$12$rcTi/.dppi/umYBbJTMdBuiGT0NENg.cMaH/aIoIU9wCri4Fkli1y';
    $superAdminPassword = '$2y$12$qRGOAn7QgBnem40Z1BGrxeN7FoOg1inQyMMo1MzKoF0mzKi0UbOve';

    $insertAdmin = $pdo->prepare(
        "INSERT INTO users (id, email, roles, password, username, active, created_at)
         SELECT 1, 'admin@clouddevfusion.com', '[\"ROLE_ADMIN\"]', :p, 'Admin', true, NOW()
         WHERE EXISTS (
             SELECT 1 FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
         )
         AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@clouddevfusion.com')"
    );
    $insertAdmin->execute(['p' => $adminPassword]);

    $insertSuper = $pdo->prepare(
        "INSERT INTO users (id, email, roles, password, username, active, created_at)
         SELECT 2, 'superadmin@clouddevfusion.com', '[\"ROLE_SUPER_ADMIN\"]', :p, 'Super Admin', true, NOW()
         WHERE EXISTS (
             SELECT 1 FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
         )
         AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'superadmin@clouddevfusion.com')"
    );
    $insertSuper->execute(['p' => $superAdminPassword]);

    $pdo->exec(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'roles'
    ) THEN
        PERFORM setval('users_id_seq', GREATEST((SELECT COALESCE(MAX(id), 0) FROM users), 1));
    END IF;
END $$;
SQL);

    $mark = $pdo->prepare(
        'INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (:version, NOW(), 0)'
    );
    $mark->execute(['version' => $version]);

    echo "Migration applied successfully.\n";
    printCounts($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}

function createPdo(string $databaseUrl): PDO
{
    $parts = parse_url($databaseUrl);
    if (!\is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
        throw new RuntimeException('Unsupported DATABASE_URL.');
    }

    $dbname = ltrim((string) ($parts['path'] ?? ''), '/');
    $user = urldecode((string) ($parts['user'] ?? ''));
    $password = urldecode((string) ($parts['pass'] ?? ''));
    $host = (string) ($parts['host'] ?? '127.0.0.1');
    $port = (int) ($parts['port'] ?? 5432);

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $dbname);

    return new PDO($dsn, $user, $password);
}

function printCounts(PDO $pdo): void
{
    $intranetCount = 0;
    $siteCount = 0;

    try {
        $intranetCount = (int) $pdo->query('SELECT COUNT(*) FROM intranet_users')->fetchColumn();
    } catch (Throwable) {
    }

    try {
        $siteCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable) {
    }

    echo "intranet_users rows: {$intranetCount}\n";
    echo "site users rows: {$siteCount}\n";
}
