<?php
/**
 * Script pour vider complètement le cache Symfony
 * Usage: https://academy.clouddevfusion.com/backend/public/clear-cache-complete.php
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
    <h1>🧹 Vidage complet du Cache Symfony</h1>
    
    <?php
    try {
        require __DIR__ . '/../vendor/autoload.php';
        
        // Charger le .env
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = new Symfony\Component\Dotenv\Dotenv();
            $dotenv->load(__DIR__ . '/../.env');
        }
        
        $kernel = new App\Kernel('prod', false);
        $kernel->boot();
        
        echo "<div class='info'>📋 Suppression du cache...</div>";
        echo "<pre>";
        
        // Supprimer le cache prod
        $cacheDir = __DIR__ . '/../var/cache/prod';
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
            echo "✅ $count fichiers supprimés du cache prod\n";
        }
        
        // Vider OPcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
            echo "✅ OPcache vidé\n";
        }
        
        // Exécuter cache:clear via console
        $application = new Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);
        
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'cache:clear',
            '--env' => 'prod',
            '--no-warmup' => true,
        ]);
        
        $output = new Symfony\Component\Console\Output\BufferedOutput();
        $application->run($input, $output);
        
        echo $output->fetch();
        
        echo "</pre>";
        echo "<div class='success'>✅ Cache vidé complètement !</div>";
        echo "<div class='info'>📋 Testez maintenant:</div>";
        echo "<ul>";
        echo "<li><a href='/api/courses' style='color: #569cd6;'>/api/courses</a></li>";
        echo "<li><a href='/api/testimonials' style='color: #569cd6;'>/api/testimonials</a></li>";
        echo "<li><a href='/api/home_banners' style='color: #569cd6;'>/api/home_banners</a></li>";
        echo "</ul>";
        
    } catch (\Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>
