<?php
/**
 * Script pour vérifier les routes disponibles
 */

$backendDir = __DIR__ . '/../../';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification des Routes</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🔍 Vérification des Routes</h1>
    
    <?php
    // Chemins possibles
    $possiblePaths = [
        '/home/race8462/academy.clouddevfusion.com/race8462/backend/',
        '/home/race8462/www/backend/',
        $backendDir
    ];
    
    $backendPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path . 'bin/console')) {
            $backendPath = $path;
            break;
        }
    }
    
    if (!$backendPath) {
        echo "<div class='error'>❌ Impossible de trouver le chemin du backend</div>";
        exit;
    }
    
    echo "<div class='info'>✅ Backend trouvé: $backendPath</div>";
    echo "<pre>";
    
    // Changer vers le répertoire backend
    chdir($backendPath);
    
    // Exécuter debug:router pour voir les routes
    $command = "php bin/console debug:router | grep -i exam 2>&1";
    echo "Exécution: $command\n\n";
    
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    if (count($output) > 0) {
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    } else {
        echo "Aucune route 'exam' trouvée\n";
    }
    
    echo "\n--- Toutes les routes API ---\n";
    $command2 = "php bin/console debug:router | grep '/api/' 2>&1";
    exec($command2, $output2, $returnVar2);
    
    foreach ($output2 as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "</pre>";
    
    // Vérifier si le contrôleur existe
    echo "<h2>📁 Vérification du Contrôleur</h2>";
    echo "<pre>";
    $controllerPath = $backendPath . 'src/Controller/ExamVoucherController.php';
    if (file_exists($controllerPath)) {
        echo "✅ ExamVoucherController.php existe\n";
        echo "Taille: " . filesize($controllerPath) . " bytes\n";
        echo "Dernière modification: " . date('Y-m-d H:i:s', filemtime($controllerPath)) . "\n";
    } else {
        echo "❌ ExamVoucherController.php n'existe pas\n";
    }
    echo "</pre>";
    ?>
</body>
</html>
