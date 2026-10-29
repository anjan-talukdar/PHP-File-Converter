<?php
declare(strict_types=1);
if (PHP_VERSION_ID < 80300) { http_response_code(500); exit('PHP 8.3 or newer is required.'); }
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) { $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require $file; }
});
$config = require __DIR__ . '/config/config.php';
foreach (['uploads','temp'] as $folder) { $path = __DIR__ . '/storage/' . $folder; if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) throw new RuntimeException('Unable to initialize private storage.'); }
session_name('file_converter');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']),'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
foreach (glob(__DIR__ . '/storage/temp/*', GLOB_ONLYDIR) ?: [] as $dir) {
    if (preg_match('/^[a-f0-9-]{36}$/i', basename($dir)) && (filemtime($dir) ?: time()) < time() - $config['retention_seconds']) App\Security::removeTree($dir);
}
