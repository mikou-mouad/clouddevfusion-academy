<?php
/**
 * Script temporaire pour exécuter les migrations Doctrine
 * 
 * SÉCURITÉ: Ce script doit être supprimé après utilisation !
 * 
 * Usage: Accédez à https://academy.clouddevfusion.com/backend/public/run-migrations.php
 * OU exécutez via SSH: php backend/public/run-migrations.php
 */

// Vérification de sécurité basique (à améliorer selon vos besoins)
$allowedIPs = []; // Ajoutez votre IP si nécessaire, ou laissez vide pour désactiver
$secretToken = 'migration_2024_12_24_temp_token'; // Changez ce token !

// Vérifier le token si fourni
if (isset($_GET['token']) && $_GET['token'] === $secretToken) {
    // Token valide, continuer
} elseif (!empty($allowedIPs) && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIPs)) {
    http_response_code(403);
    die('Access denied. IP not allowed.');
} else {
    // Si aucun token et aucune IP autorisée, demander le token
    if (!isset($_GET['token'])) {
        http_response_code(401);
        die('Token required. Add ?token=YOUR_TOKEN to the URL');
    }
    die('Invalid token');
}

// Charger l'autoloader Symfony
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/../../.env')) {
    (new Dotenv())->load(__DIR__ . '/../../.env');
}

// Créer l'application Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'prod', (bool) ($_ENV['APP_DEBUG'] ?? false));
$kernel->boot();

// Obtenir le service de migrations
$container = $kernel->getContainer();
$migrationService = $container->get('doctrine.migrations.dependency_factory');

// Headers pour affichage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exécution des Migrations</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        .warning { color: #dcdcaa; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Exécution des Migrations Doctrine</h1>
    
    <?php
    try {
        echo "<div class='info'>📋 Vérification de l'état actuel des migrations...</div>\n";
        
        // Exécuter la commande de statut
        $statusCommand = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'doctrine:migrations:status',
        ]);
        $statusCommand->setInteractive(false);
        
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);
        
        echo "<pre>";
        $application->run($statusCommand, $output);
        echo htmlspecialchars($output->fetch());
        echo "</pre>";
        
        echo "<div class='info'>🚀 Exécution des migrations...</div>\n";
        
        // Exécuter les migrations
        $migrateCommand = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ]);
        $migrateCommand->setInteractive(false);
        
        $migrateOutput = new \Symfony\Component\Console\Output\BufferedOutput();
        $application->run($migrateCommand, $migrateOutput);
        
        echo "<pre>";
        echo htmlspecialchars($migrateOutput->fetch());
        echo "</pre>";
        
        echo "<div class='success'>✅ Migrations exécutées avec succès !</div>\n";
        
        echo "<div class='info'>🔍 Vérification finale...</div>\n";
        $finalStatusOutput = new \Symfony\Component\Console\Output\BufferedOutput();
        $application->run($statusCommand, $finalStatusOutput);
        
        echo "<pre>";
        echo htmlspecialchars($finalStatusOutput->fetch());
        echo "</pre>";
        
    } catch (\Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
    
    <hr>
    <div class='warning'>⚠️ IMPORTANT: Supprimez ce fichier après utilisation pour des raisons de sécurité !</div>
    <div class='info'>📋 Testez les APIs:</div>
    <ul>
        <li><a href="/api/courses" style="color: #569cd6;">/api/courses</a></li>
        <li><a href="/api/testimonials" style="color: #569cd6;">/api/testimonials</a></li>
        <li><a href="/api/exam_vouchers" style="color: #569cd6;">/api/exam_vouchers</a></li>
    </ul>
</body>
</html>
