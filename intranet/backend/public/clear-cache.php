<?php
/**
 * Script pour vider le cache Symfony en production
 * Usage: https://academy.clouddevfusion.com/backend/public/clear-cache.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vider le Cache Symfony</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧹 Vidage du Cache Symfony</h1>
    
    <?php
    $cacheDir = __DIR__ . '/../../var/cache/prod';
    
    echo "<div class='info'>📋 Suppression du cache...</div>";
    echo "<pre>";
    
    if (is_dir($cacheDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        $count = 0;
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
                $count++;
            }
        }
        
        echo "✅ $count fichiers supprimés\n";
        echo "✅ Cache vidé avec succès\n";
    } else {
        echo "ℹ️ Le dossier cache n'existe pas encore\n";
    }
    
    // Vider aussi le cache de l'opcache si possible
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✅ OPcache vidé\n";
    }
    
    echo "</pre>";
    
    echo "<div class='success'>✅ Cache vidé !</div>";
    echo "<div class='info'>📋 Testez maintenant l'API: <a href='/api/exam_vouchers' style='color: #569cd6;'>/api/exam_vouchers</a></div>";
    ?>
</body>
</html>
