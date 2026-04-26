<?php
/**
 * Script pour vider le cache Symfony via la console
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vider Cache Symfony</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🧹 Vidage du Cache Symfony</h1>
    
    <?php
    // Chemins possibles du backend
    $possiblePaths = [
        __DIR__ . '/../../',
        '/home/race8462/academy.clouddevfusion.com/race8462/backend/',
        '/home/race8462/www/backend/',
        dirname(__DIR__) . '/',
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
        echo "<pre>";
        foreach ($possiblePaths as $path) {
            echo "Testé: $path\n";
            echo "  Existe: " . (file_exists($path) ? "Oui" : "Non") . "\n";
            echo "  Console: " . (file_exists($path . 'bin/console') ? "Oui" : "Non") . "\n\n";
        }
        echo "</pre>";
        echo "<div class='info'>📋 Veuillez exécuter manuellement dans le terminal O2Switch:</div>";
        echo "<pre>cd /home/race8462/academy.clouddevfusion.com/race8462/backend
php bin/console cache:clear --env=prod --no-warmup</pre>";
        exit;
    }
    
    echo "<div class='info'>✅ Backend trouvé: $backendPath</div>";
    echo "<pre>";
    
    // Changer vers le répertoire backend
    chdir($backendPath);
    
    // Exécuter cache:clear
    $command = "php bin/console cache:clear --env=prod --no-warmup 2>&1";
    echo "Exécution: $command\n\n";
    
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    if ($returnVar === 0) {
        echo "\n<div class='success'>✅ Cache vidé avec succès!</div>";
    } else {
        echo "\n<div class='error'>❌ Erreur lors du vidage du cache (code: $returnVar)</div>";
        echo "<div class='info'>📋 Essayez manuellement dans le terminal O2Switch:</div>";
        echo "<pre>cd $backendPath
php bin/console cache:clear --env=prod --no-warmup</pre>";
    }
    
    echo "</pre>";
    
    echo "<div class='info'>📋 Testez maintenant:</div>";
    echo "<ul>";
    echo "<li><a href='/api/courses' style='color: #569cd6;'>GET /api/courses</a></li>";
    echo "<li><a href='/api/testimonials' style='color: #569cd6;'>GET /api/testimonials</a></li>";
    echo "<li><a href='/api/exam_vouchers' style='color: #569cd6;'>GET /api/exam_vouchers</a></li>";
    echo "</ul>";
    ?>
</body>
</html>
