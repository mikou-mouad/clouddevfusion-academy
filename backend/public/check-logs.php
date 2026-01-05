<?php
/**
 * Script pour afficher les logs d'erreur Symfony
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs d'Erreur Symfony</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 600px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>📋 Logs d'Erreur Symfony</h1>
    
    <?php
    $logFile = __DIR__ . '/../../var/log/prod.log';
    
    if (file_exists($logFile)) {
        echo "<div class='info'>📋 Dernières erreurs (50 dernières lignes):</div>";
        echo "<pre>";
        $lines = file($logFile);
        $lastLines = array_slice($lines, -50);
        echo htmlspecialchars(implode('', $lastLines));
        echo "</pre>";
    } else {
        echo "<div class='error'>❌ Fichier de log non trouvé: $logFile</div>";
    }
    ?>
</body>
</html>
