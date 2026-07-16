<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
$autoload = __DIR__.'/../vendor/autoload.php';
if (!is_file($autoload)) { http_response_code(503); echo "Vendor not ready\n"; exit(1); }
require_once $autoload;
use Symfony\Component\Dotenv\Dotenv;
if (is_file(__DIR__.'/../.env')) { (new Dotenv())->bootEnv(__DIR__.'/../.env'); }
$token = (string) ($_GET['token'] ?? '');
if ($token === '' || !hash_equals((string) ($_ENV['APP_SECRET'] ?? ''), $token)) { http_response_code(401); echo "Unauthorized\n"; exit(1); }
foreach ([
    'upload_max_filesize', 'post_max_size', 'user_ini.filename', 'user_ini.cache_ttl',
    'memory_limit', 'max_execution_time',
] as $k) {
    echo $k.'='.ini_get($k)."\n";
}
echo 'cwd='.getcwd()."\n";
echo 'script='.__FILE__."\n";
echo 'user_ini_public='.(is_file(__DIR__.'/.user.ini') ? 'yes' : 'no')."\n";
echo 'php_ini_public='.(is_file(__DIR__.'/php.ini') ? 'yes' : 'no')."\n";
echo 'sapi='.PHP_SAPI."\n";
echo 'version='.PHP_VERSION."\n";
