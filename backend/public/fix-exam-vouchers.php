<?php
/**
 * Script pour diagnostiquer et corriger le problème ExamVoucher
 */

// Charger Symfony
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../../.env')) {
    (new Dotenv())->load(__DIR__ . '/../../.env');
}

$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'prod', (bool) ($_ENV['APP_DEBUG'] ?? false));
$kernel->boot();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic ExamVoucher</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic ExamVoucher</h1>
    
    <?php
    try {
        $container = $kernel->getContainer();
        
        echo "<div class='info'>📋 Vérification des services...</div>";
        echo "<pre>";
        
        // Vérifier si ExamVoucherDenormalizer est enregistré
        try {
            $denormalizer = $container->get('App\Serializer\ExamVoucherDenormalizer');
            echo "✅ ExamVoucherDenormalizer trouvé\n";
        } catch (\Exception $e) {
            echo "❌ ExamVoucherDenormalizer non trouvé: " . $e->getMessage() . "\n";
        }
        
        // Vérifier si ExamVoucherProcessor est enregistré
        try {
            $processor = $container->get('App\State\ExamVoucherProcessor');
            echo "✅ ExamVoucherProcessor trouvé\n";
        } catch (\Exception $e) {
            echo "❌ ExamVoucherProcessor non trouvé: " . $e->getMessage() . "\n";
        }
        
        // Vérifier l'entité
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $metadata = $entityManager->getClassMetadata('App\Entity\ExamVoucher');
        echo "✅ Entité ExamVoucher reconnue par Doctrine\n";
        echo "   Table: " . $metadata->getTableName() . "\n";
        
        // Test de désérialisation
        echo "\n<div class='info'>📋 Test de désérialisation...</div>\n";
        $serializer = $container->get('serializer');
        $testData = [
            'code' => 'TEST-DIAG',
            'examCode' => 'AZ-104',
            'type' => 'voucher-only',
            'price' => '100.00',
            'validityPeriod' => 365,
            'isActive' => true
        ];
        
        try {
            $voucher = $serializer->deserialize(
                json_encode($testData),
                'App\Entity\ExamVoucher',
                'json',
                ['groups' => ['exam_voucher:write']]
            );
            echo "✅ Désérialisation réussie\n";
            echo "   Code: " . $voucher->getCode() . "\n";
            echo "   Prix: " . $voucher->getPrice() . "\n";
        } catch (\Exception $e) {
            echo "❌ Erreur de désérialisation: " . $e->getMessage() . "\n";
            echo "   Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
        }
        
        echo "</pre>";
        
    } catch (\Exception $e) {
        echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>
