<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
if (is_file(__DIR__.'/../.env')) { (new Dotenv())->bootEnv(__DIR__.'/../.env'); }
$token = (string) ($_GET['token'] ?? '');
if ($token === '' || !hash_equals((string) ($_ENV['APP_SECRET'] ?? ''), $token)) { http_response_code(401); echo "Unauthorized\n"; exit(1); }
$url = (string) ($_ENV['DATABASE_URL'] ?? '');
$p = parse_url($url);
$pdo = new PDO(sprintf('pgsql:host=%s;port=%d;dbname=%s', $p['host']??'127.0.0.1', (int)($p['port']??5432), ltrim($p['path']??'','/')), urldecode($p['user']??''), urldecode($p['pass']??''));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach (['trainers_id_seq'=>'trainers','intranet_users_id_seq'=>'intranet_users','students_id_seq'=>'students'] as $seq => $table) {
    try {
        $max = (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM {$table}")->fetchColumn();
        $cur = (int) $pdo->query("SELECT last_value FROM {$seq}")->fetchColumn();
        echo "{$table}: max_id={$max} seq_last={$cur}\n";
        if ($cur < $max) {
            $pdo->exec("SELECT setval('{$seq}', {$max})");
            echo "  -> fixed setval to {$max}\n";
        }
    } catch (Throwable $e) {
        echo "{$table}: ".$e->getMessage()."\n";
    }
}
// FK check
$rows = $pdo->query("SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid = 'trainers'::regclass AND contype = 'f'")->fetchAll(PDO::FETCH_ASSOC);
echo "trainers FKs:\n";
foreach ($rows as $r) { echo "  {$r['conname']}: {$r['pg_get_constraintdef']}\n"; }
