<?php
/**
 * Script PHP pour exécuter les migrations SQL directement
 * Contourne le problème de compatibilité Doctrine/PostgreSQL
 */

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
        .warning { color: #dcdcaa; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Exécution des Migrations SQL Directes</h1>
    
    <?php
    // Charger les variables d'environnement depuis plusieurs emplacements possibles
    $envFiles = [
        __DIR__ . '/../../.env',
        __DIR__ . '/../../../.env',
        '/home/race8462/academy.clouddevfusion.com/race8462/backend/.env',
        '/home/race8462/www/backend/.env'
    ];
    
    $dbUrl = '';
    
    foreach ($envFiles as $envFile) {
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    if ($key === 'DATABASE_URL') {
                        $dbUrl = $value;
                        break 2;
                    }
                }
            }
        }
    }
    
    // Si toujours pas trouvé, utiliser les valeurs par défaut pour O2Switch
    if (empty($dbUrl)) {
        // Valeurs par défaut pour O2Switch PostgreSQL
        $dbUser = 'race8462_race8462';
        $dbPass = 'clouddevfusion';
        $dbHost = 'localhost';
        $dbPort = '5432';
        $dbName = 'race8462_academy';
        
        echo "<div class='warning'>⚠️ DATABASE_URL non trouvé, utilisation des valeurs par défaut O2Switch</div><br>";
    } else {
    
        // Parser DATABASE_URL PostgreSQL
        // Format: postgresql://user:password@host:port/database
        preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dbUrl, $matches);
        
        if (count($matches) !== 6) {
            echo "<div class='error'>❌ Format DATABASE_URL invalide, utilisation des valeurs par défaut</div><br>";
            $dbUser = 'race8462_race8462';
            $dbPass = 'clouddevfusion';
            $dbHost = 'localhost';
            $dbPort = '5432';
            $dbName = 'race8462_academy';
        } else {
            $dbUser = $matches[1];
            $dbPass = $matches[2];
            $dbHost = $matches[3];
            $dbPort = $matches[4];
            $dbName = $matches[5];
        }
    }
    
    try {
        // Connexion PostgreSQL
        $conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass");
        
        if (!$conn) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        echo "<div class='info'>✅ Connexion à la base de données réussie</div><br>";
        
        // Lire le fichier SQL
        $sqlFile = __DIR__ . '/../migrations-execute-direct.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Fichier SQL non trouvé: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        echo "<div class='info'>📋 Exécution des migrations SQL...</div>";
        echo "<pre>";
        
        // Exécuter le SQL
        $result = pg_query($conn, $sql);
        
        if ($result) {
            echo "<div class='success'>✅ Migrations exécutées avec succès!</div>\n\n";
            
            // Afficher les résultats
            while ($row = pg_fetch_assoc($result)) {
                foreach ($row as $key => $value) {
                    echo "$key: $value\n";
                }
            }
            
            // Vérifications
            echo "\n<div class='info'>🔍 Vérifications:</div>\n";
            
            // Vérifier video_url
            $check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name = 'testimonials' AND column_name = 'video_url'");
            if (pg_num_rows($check) > 0) {
                echo "✅ Colonne video_url existe dans testimonials\n";
            } else {
                echo "❌ Colonne video_url manquante\n";
            }
            
            // Vérifier exam_vouchers
            $check = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_name = 'exam_vouchers'");
            if (pg_num_rows($check) > 0) {
                echo "✅ Table exam_vouchers existe\n";
            } else {
                echo "❌ Table exam_vouchers manquante\n";
            }
            
        } else {
            $error = pg_last_error($conn);
            echo "<div class='error'>❌ Erreur SQL: $error</div>";
        }
        
        echo "</pre>";
        
        pg_close($conn);
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    
    <hr>
    <div class='info'>📋 Testez les APIs:</div>
    <ul>
        <li><a href="/api/courses" style="color: #569cd6;">/api/courses</a></li>
        <li><a href="/api/testimonials" style="color: #569cd6;">/api/testimonials</a></li>
        <li><a href="/api/exam_vouchers" style="color: #569cd6;">/api/exam_vouchers</a></li>
    </ul>
</body>
</html>
