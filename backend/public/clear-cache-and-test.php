<?php
/**
 * Script pour vider le cache Symfony et tester l'API
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vider Cache et Tester</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧹 Vidage du Cache et Test</h1>
    
    <?php
    $backendDir = __DIR__ . '/../../';
    $cacheDir = $backendDir . 'var/cache/prod';
    
    echo "<div class='info'>📋 Étape 1: Suppression du cache...</div>";
    echo "<pre>";
    
    if (is_dir($cacheDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        $count = 0;
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
                $count++;
            }
        }
        
        echo "✅ $count fichiers supprimés\n";
    }
    
    // Vider OPcache
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✅ OPcache vidé\n";
    }
    
    echo "</pre>";
    
    echo "<div class='info'>📋 Étape 2: Test de l'API...</div>";
    echo "<pre>";
    
    // Test GET
    $ch = curl_init('https://academy.clouddevfusion.com/api/exam_vouchers');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "GET /api/exam_vouchers: HTTP $httpCode\n";
    if ($httpCode === 200) {
        echo "<div class='success'>✅ GET fonctionne!</div>\n";
    } else {
        echo "<div class='error'>❌ GET échoue (code: $httpCode)</div>\n";
        echo "Réponse: " . htmlspecialchars(substr($response, 0, 200)) . "\n";
    }
    
    echo "</pre>";
    
    echo "<div class='info'>💡 Si l'erreur persiste, exécutez dans le terminal O2Switch:</div>";
    echo "<pre>cd /home/race8462/academy.clouddevfusion.com/race8462/backend && php bin/console cache:clear --env=prod</pre>";
    ?>
</body>
</html>
