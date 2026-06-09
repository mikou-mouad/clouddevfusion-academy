<?php

declare(strict_types=1);

header('Content-Type: application/json');

$projectDir = dirname(__DIR__);
$flag = $projectDir.'/var/migrate.pending';

if (is_file($flag) && is_file($projectDir.'/vendor/autoload.php')) {
    try {
        require_once $projectDir.'/vendor/autoload.php';

        if (is_file($projectDir.'/.env')) {
            (new Symfony\Component\Dotenv\Dotenv())->bootEnv($projectDir.'/.env');
        }

        (new App\Service\PendingMigrationRunner())->runIfPending($projectDir);
    } catch (Throwable $e) {
        error_log('Pending migration failed: '.$e->getMessage());
    }
}

echo '{"ok":true}';
