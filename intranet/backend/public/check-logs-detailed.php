<?php
/**
 * Script pour afficher les logs d'erreur détaillés
 */

$backendDir = __DIR__ . '/../../';
$logFile = $backendDir . 'var/log/prod.log';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs d'Erreur</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
        .log-entry { margin: 10px 0; padding: 10px; border-left: 3px solid #569cd6; }
        .log-error { border-left-color: #f48771; }
    </style>
</head>
<body>
    <h1>📋 Logs d'Erreur Backend</h1>
    
    <?php
    // Chemins possibles
    $possiblePaths = [
        '/home/race8462/academy.clouddevfusion.com/race8462/backend/var/log/prod.log',
        '/home/race8462/www/backend/var/log/prod.log',
        $logFile
    ];
    
    $foundLog = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $foundLog = $path;
            break;
        }
    }
    
    if ($foundLog) {
        echo "<div class='success'>✅ Fichier de log trouvé: $foundLog</div><br>";
        
        // Lire les 100 dernières lignes
        $lines = file($foundLog);
        $lastLines = array_slice($lines, -100);
        
        echo "<h2>Dernières 100 lignes:</h2>";
        echo "<pre>";
        foreach ($lastLines as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false || stripos($line, 'fatal') !== false) {
                echo "<span class='log-error'>" . htmlspecialchars($line) . "</span>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre>";
    } else {
        echo "<div class='error'>❌ Fichier de log non trouvé</div>";
        echo "<div class='info'>Chemins testés:</div><ul>";
        foreach ($possiblePaths as $path) {
            echo "<li>$path - " . (file_exists($path) ? "Existe" : "N'existe pas") . "</li>";
        }
        echo "</ul>";
    }
    
    // Tester la connexion à la base de données
    echo "<h2>🔍 Test de Connexion à la Base de Données</h2>";
    echo "<pre>";
    
    try {
        // Lire .env
        $envFile = $backendDir . '.env';
        $dbUrl = null;
        
        if (file_exists($envFile)) {
            $lines = file($envFile);
            foreach ($lines as $line) {
                if (strpos($line, 'DATABASE_URL') !== false && strpos($line, '=') !== false) {
                    $parts = explode('=', $line, 2);
                    $dbUrl = trim($parts[1] ?? '', " \t\n\r\0\x0B\"'");
                    break;
                }
            }
        }
        
        if (empty($dbUrl)) {
            // Valeurs par défaut O2Switch
            $dbUser = 'race8462_race8462';
            $dbPass = 'clouddevfusion';
            $dbHost = 'localhost';
            $dbPort = '5432';
            $dbName = 'race8462_academy';
        } else {
            // Parser DATABASE_URL PostgreSQL
            if (preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)\?/', $dbUrl, $matches)) {
                $dbUser = $matches[1];
                $dbPass = $matches[2];
                $dbHost = $matches[3];
                $dbPort = $matches[4];
                $dbName = $matches[5];
            } else {
                throw new Exception("Format DATABASE_URL invalide");
            }
        }
        
        $conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass");
        
        if (!$conn) {
            throw new Exception("Connexion échouée");
        }
        
        echo "✅ Connexion à PostgreSQL réussie\n";
        
        // Tester la table exam_vouchers
        $result = pg_query($conn, "SELECT COUNT(*) as count FROM exam_vouchers");
        if ($result) {
            $row = pg_fetch_assoc($result);
            echo "✅ Table exam_vouchers accessible - " . $row['count'] . " enregistrements\n";
        }
        
        // Tester une requête simple
        $result = pg_query($conn, "SELECT id, code, exam_code FROM exam_vouchers LIMIT 5");
        if ($result) {
            echo "✅ Requête SELECT fonctionne\n";
            $rows = pg_fetch_all($result);
            if ($rows) {
                echo "Données: " . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Aucune donnée dans la table\n";
            }
        }
        
        pg_close($conn);
        
    } catch (Exception $e) {
        echo "❌ Erreur: " . htmlspecialchars($e->getMessage()) . "\n";
    }
    
    echo "</pre>";
    ?>
</body>
</html>
