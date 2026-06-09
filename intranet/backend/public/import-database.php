<?php
/**
 * Import var/intranet_db_dump.sql.gz on the production server (localhost PostgreSQL).
 * Usage: /intranet/backend/public/import-database.php?token=APP_SECRET
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

use App\Service\DatabaseDumpImporter;
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

try {
    $result = (new DatabaseDumpImporter())->import(dirname(__DIR__));
    echo "PostgreSQL import OK\n";
    if ($result['output'] !== '') {
        echo $result['output']."\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}
