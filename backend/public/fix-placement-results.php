<?php

declare(strict_types=1);

$token = $_GET['token'] ?? '';
if ($token !== 'fix_placement_2026') {
    http_response_code(403);
    exit('Accès refusé.');
}

header('Content-Type: text/plain; charset=utf-8');

try {
    require __DIR__ . '/../vendor/autoload.php';

    if (file_exists(__DIR__ . '/../.env')) {
        (new Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
    }

    $kernel = new App\Kernel('prod', false);
    $kernel->boot();
    $connection = $kernel->getContainer()->get('doctrine.dbal.default_connection');

    $connection->executeStatement(<<<'SQL'
        DO $$ BEGIN
            ALTER TABLE placement_test_results ADD COLUMN user_phone VARCHAR(50) DEFAULT NULL;
        EXCEPTION
            WHEN duplicate_column THEN NULL;
        END $$;
    SQL);

    echo "OK: colonne user_phone vérifiée sur placement_test_results\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERREUR: ' . $e->getMessage() . "\n";
}
