<?php
/**
 * Script pour vérifier la structure de la table exam_vouchers
 */

header('Content-Type: text/html; charset=utf-8');

// Charger les variables d'environnement
$envFile = __DIR__ . '/../../.env';
$dbUrl = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, 'DATABASE_URL') !== false && strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $dbUrl = trim($value, '"\'');
            break;
        }
    }
}

// Valeurs par défaut si .env non trouvé
if (empty($dbUrl)) {
    $dbUser = 'race8462_race8462';
    $dbPass = 'clouddevfusion';
    $dbHost = 'localhost';
    $dbPort = '5432';
    $dbName = 'race8462_academy';
} else {
    preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dbUrl, $matches);
    if (count($matches) === 6) {
        $dbUser = $matches[1];
        $dbPass = $matches[2];
        $dbHost = $matches[3];
        $dbPort = $matches[4];
        $dbName = $matches[5];
    } else {
        $dbUser = 'race8462_race8462';
        $dbPass = 'clouddevfusion';
        $dbHost = 'localhost';
        $dbPort = '5432';
        $dbName = 'race8462_academy';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Structure de la Table exam_vouchers</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #333; }
    </style>
</head>
<body>
    <h1>🔍 Structure de la Table exam_vouchers</h1>
    
    <?php
    try {
        $conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass");
        
        if (!$conn) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        echo "<div class='info'>✅ Connexion réussie</div><br>";
        
        // Vérifier si la table existe
        $check = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_name = 'exam_vouchers'");
        if (pg_num_rows($check) === 0) {
            echo "<div class='error'>❌ La table exam_vouchers n'existe pas</div>";
            exit;
        }
        
        echo "<div class='success'>✅ La table exam_vouchers existe</div><br>";
        
        // Récupérer la structure de la table
        $result = pg_query($conn, "
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default
            FROM information_schema.columns 
            WHERE table_name = 'exam_vouchers'
            ORDER BY ordinal_position
        ");
        
        echo "<h2>Structure des colonnes:</h2>";
        echo "<table>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
        
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['column_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['data_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['is_nullable']) . "</td>";
            echo "<td>" . htmlspecialchars($row['column_default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Vérifier le type de booking_steps
        $result = pg_query($conn, "
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'exam_vouchers' AND column_name = 'booking_steps'
        ");
        
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $dataType = $row['data_type'];
            echo "<br><div class='info'>Type de booking_steps: <strong>$dataType</strong></div>";
            
            if ($dataType === 'jsonb' || $dataType === 'json') {
                echo "<div class='success'>✅ Type JSON correct</div>";
            } else {
                echo "<div class='error'>❌ Type incorrect: $dataType (attendu: json ou jsonb)</div>";
            }
        }
        
        // Compter les enregistrements
        $count = pg_query($conn, "SELECT COUNT(*) as count FROM exam_vouchers");
        $countRow = pg_fetch_assoc($count);
        echo "<br><div class='info'>Nombre d'enregistrements: <strong>" . $countRow['count'] . "</strong></div>";
        
        pg_close($conn);
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
</body>
</html>
