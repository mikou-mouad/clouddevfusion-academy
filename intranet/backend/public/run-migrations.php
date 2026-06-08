<?php
/**
 * Run Doctrine migrations on the production server (localhost DB access).
 * Called by CI after FTP deploy: /intranet/backend/public/run-migrations.php?token=APP_SECRET
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

if (is_file(__DIR__ . '/../.env')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

$expectedToken = (string) ($_ENV['APP_SECRET'] ?? '');
$providedToken = (string) ($_GET['token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Unauthorized\n";
    exit(1);
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $kernel = new App\Kernel($_ENV['APP_ENV'] ?? 'prod', (bool) ($_ENV['APP_DEBUG'] ?? false));
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $statusInput = new ArrayInput(['command' => 'doctrine:migrations:status']);
    $statusInput->setInteractive(false);
    $statusOutput = new BufferedOutput();
    $application->run($statusInput, $statusOutput);
    echo "=== Status ===\n";
    echo $statusOutput->fetch();

    $migrateInput = new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--no-interaction' => true,
        '--allow-no-migration' => true,
    ]);
    $migrateInput->setInteractive(false);
    $migrateOutput = new BufferedOutput();
    $exitCode = $application->run($migrateInput, $migrateOutput);
    echo "=== Migrate ===\n";
    echo $migrateOutput->fetch();

    if ($exitCode !== 0) {
        http_response_code(500);
        echo "Migration failed (exit code {$exitCode})\n";
        exit($exitCode);
    }

    echo "Migrations exécutées avec succès\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
