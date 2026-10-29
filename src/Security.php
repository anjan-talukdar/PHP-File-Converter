<?php
declare(strict_types=1);
namespace App;
final class Security
{
    public static function removeTree(string $dir): void {
        if (!is_dir($dir) || is_link($dir)) return;
        foreach (new \FilesystemIterator($dir) as $item) { if ($item->isDir() && !$item->isLink()) self::removeTree($item->getPathname()); else @unlink($item->getPathname()); }
        @rmdir($dir);
    }
    public static function cleanName(string $name): string {
        $name = basename(str_replace('\\', '/', $name)); $name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', $name) ?? 'file'; return substr(trim($name, '. '), 0, 120) ?: 'file';
    }
    public static function uuid(): string { return sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(6))); }
    public static function assertCsrf(): void {
        $given = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
        if (!is_string($given) || !hash_equals($_SESSION['csrf'] ?? '', $given)) { http_response_code(403); throw new \RuntimeException('Security token expired. Refresh the page and try again.'); }
    }
}
