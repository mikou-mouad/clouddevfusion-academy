<?php
/**
 * Script pour réparer le backend après déploiement
 * - Vide le cache Symfony
 * - Teste les connexions
 */

$backendDir = __DIR__ . '/../../';
$cacheDir = $backendDir . 'var/cache/prod';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Réparation Backend</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        .warning { color: #dcdcaa; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h2 { color: #4ec9b0; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔧 Réparation du Backend</h1>
    
    <?php
    echo "<h2>1️⃣ Suppression du cache Symfony</h2>";
    echo "<pre>";
    
    $count = 0;
    if (is_dir($cacheDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
                $count++;
            }
        }
        @rmdir($cacheDir);
    }
    
    // Vider aussi var/cache/dev si existe
    $devCacheDir = $backendDir . 'var/cache/dev';
    if (is_dir($devCacheDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($devCacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
                $count++;
            }
        }
        @rmdir($devCacheDir);
    }
    
    echo "✅ $count fichiers supprimés\n";
    
    // Vider OPcache
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✅ OPcache vidé\n";
    }
    
    // Vider APCu si disponible
    if (function_exists('apcu_clear_cache')) {
        apcu_clear_cache();
        echo "✅ APCu cache vidé\n";
    }
    
    echo "</pre>";
    
    echo "<h2>2️⃣ Vérification des fichiers essentiels</h2>";
    echo "<pre>";
    
    $essentialFiles = [
        'config/services.yaml',
        'src/Kernel.php',
        'public/index.php',
        '.env'
    ];
    
    foreach ($essentialFiles as $file) {
        $path = $backendDir . $file;
        if (file_exists($path)) {
            echo "✅ $file existe\n";
        } else {
            echo "❌ $file MANQUANT\n";
        }
    }
    
    echo "</pre>";
    
    echo "<h2>3️⃣ Test des APIs</h2>";
    echo "<pre>";
    
    $baseUrl = 'https://academy.clouddevfusion.com';
    $apis = [
        '/api/courses' => 'GET',
        '/api/testimonials' => 'GET',
        '/api/exam_vouchers' => 'GET'
    ];
    
    foreach ($apis as $endpoint => $method) {
        $url = $baseUrl . $endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ $endpoint ($httpCode) - OK\n";
        } elseif ($httpCode === 404) {
            echo "⚠️  $endpoint ($httpCode) - Not Found\n";
        } elseif ($httpCode === 500) {
            echo "❌ $endpoint ($httpCode) - Internal Server Error\n";
        } else {
            echo "⚠️  $endpoint ($httpCode) - Status inattendu\n";
        }
    }
    
    echo "</pre>";
    
    echo "<div class='info'>📋 Instructions:</div>";
    echo "<ol>";
    echo "<li>Si le cache n'a pas été vidé correctement, exécutez dans le terminal O2Switch:<br>";
    echo "<code>cd /home/race8462/academy.clouddevfusion.com/race8462/backend && php bin/console cache:clear --env=prod --no-warmup</code></li>";
    echo "<li>Vérifiez les permissions des dossiers:<br>";
    echo "<code>chmod -R 755 var/</code></li>";
    echo "<li>Vérifiez les logs d'erreur:<br>";
    echo "<code>tail -50 var/log/prod.log</code></li>";
    echo "</ol>";
    ?>
</body>
</html>
