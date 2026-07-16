<?php
/**
 * Full PostgreSQL backup on the production server (localhost DB access).
 * Usage: /intranet/backend/public/backup-database.php?token=APP_SECRET
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

set_time_limit(600);

$databaseUrl = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
if ($databaseUrl === '') {
    http_response_code(500);
    echo "DATABASE_URL is not configured.\n";
    exit(1);
}

$parts = parse_url($databaseUrl);
if (!\is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
    http_response_code(500);
    echo "Unsupported DATABASE_URL.\n";
    exit(1);
}

$dbname = ltrim((string) ($parts['path'] ?? ''), '/');
$user = urldecode((string) ($parts['user'] ?? ''));
$password = urldecode((string) ($parts['pass'] ?? ''));
$host = (string) ($parts['host'] ?? '127.0.0.1');
$port = (int) ($parts['port'] ?? 5432);

$varDir = dirname(__DIR__).'/var';
if (!is_dir($varDir) && !mkdir($varDir, 0755, true) && !is_dir($varDir)) {
    http_response_code(500);
    echo "Cannot create var directory.\n";
    exit(1);
}

$timestamp = gmdate('Ymd_His');
$output = $varDir.'/full_db_backup_'.$timestamp.'.sql.gz';

$pgDump = trim((string) shell_exec('command -v pg_dump 2>/dev/null'));
if ($pgDump === '' || !is_executable($pgDump)) {
    foreach (['/usr/bin/pg_dump', '/usr/local/bin/pg_dump'] as $candidate) {
        if (is_executable($candidate)) {
            $pgDump = $candidate;
            break;
        }
    }
}

if ($pgDump === '') {
    http_response_code(500);
    echo "pg_dump not found on server.\n";
    exit(1);
}

$command = sprintf(
    'PGPASSWORD=%s %s -h %s -p %d -U %s -d %s --no-owner --no-acl 2>&1 | gzip -9 > %s',
    escapeshellarg($password),
    escapeshellarg($pgDump),
    escapeshellarg($host),
    $port,
    escapeshellarg($user),
    escapeshellarg($dbname),
    escapeshellarg($output),
);

exec($command, $lines, $exitCode);
if ($exitCode !== 0 || !is_file($output) || filesize($output) < 100) {
    http_response_code(500);
    echo "Backup failed (exit {$exitCode}).\n";
    if ($lines !== []) {
        echo implode("\n", $lines)."\n";
    }
    exit(1);
}

echo "PostgreSQL backup OK\n";
echo 'File: '.$output."\n";
echo 'Size: '.filesize($output)." bytes\n";
echo "Keep this file until migration tests pass.\n";
