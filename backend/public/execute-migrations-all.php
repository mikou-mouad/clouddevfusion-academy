<?php
/**
 * Script pour exécuter toutes les migrations SQL directement
 * SÉCURITÉ: Ce script doit être supprimé après utilisation !
 * 
 * Usage: https://academy.clouddevfusion.com/backend/public/execute-migrations-all.php?token=migration_2024_12_27
 */

// Vérification de sécurité
$token = $_GET['token'] ?? '';
$expectedToken = 'migration_2024_12_27';

if ($token !== $expectedToken) {
    http_response_code(403);
    die('Accès refusé. Token invalide.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exécution des Migrations SQL</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Exécution des Migrations SQL</h1>
    
    <?php
    try {
        require __DIR__ . '/../vendor/autoload.php';
        
        // Charger le .env manuellement
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = new Symfony\Component\Dotenv\Dotenv();
            $dotenv->load(__DIR__ . '/../.env');
        }
        
        $kernel = new App\Kernel('prod', false);
        $kernel->boot();
        
        $connection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
        
        echo "<div class='info'>📋 Lecture du fichier SQL...</div>";
        $sqlFile = __DIR__ . '/../migrations-execute-all.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Fichier SQL non trouvé: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        echo "<div class='info'>📝 Exécution des migrations...</div>";
        echo "<pre>";
        
        // Exécuter le SQL
        $connection->executeStatement($sql);
        
        echo "✅ Migrations exécutées avec succès\n\n";
        
        // Vérifier les tables créées
        echo "📋 Vérification des tables créées:\n";
        $tables = ['users', 'home_banners', 'audit_logs'];
        foreach ($tables as $table) {
            $exists = $connection->executeQuery("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')")->fetchOne();
            if ($exists) {
                echo "  ✅ Table '$table' existe\n";
            } else {
                echo "  ❌ Table '$table' n'existe pas\n";
            }
        }
        
        // Vérifier la colonne access_delay
        $columnExists = $connection->executeQuery("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'courses' AND column_name = 'access_delay')")->fetchOne();
        if ($columnExists) {
            echo "  ✅ Colonne 'access_delay' existe dans 'courses'\n";
        } else {
            echo "  ❌ Colonne 'access_delay' n'existe pas\n";
        }
        
        // Vérifier les utilisateurs
        echo "\n📋 Utilisateurs créés:\n";
        $users = $connection->executeQuery("SELECT id, email, username, roles FROM users ORDER BY id")->fetchAllAssociative();
        foreach ($users as $user) {
            echo "  - ID: {$user['id']}, Email: {$user['email']}, Username: {$user['username']}, Roles: {$user['roles']}\n";
        }
        
        echo "</pre>";
        echo "<div class='success'>✅ Toutes les migrations ont été exécutées avec succès !</div>";
        echo "<div class='info'>📋 Prochaines étapes:</div>";
        echo "<ul>";
        echo "<li>Vider le cache: <code>php bin/console cache:clear --env=prod</code></li>";
        echo "<li>Tester l'API: <a href='/api/courses' style='color: #569cd6;'>/api/courses</a></li>";
        echo "<li>Tester le login: <a href='/api/login' style='color: #569cd6;'>/api/login</a></li>";
        echo "</ul>";
        echo "<div class='error'><strong>⚠️ IMPORTANT:</strong> Supprimez ce fichier après utilisation pour des raisons de sécurité.</div>";
        
    } catch (\Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>


