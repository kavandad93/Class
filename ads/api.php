<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_auth();

function adFiles(string $dir): array {
    if (!is_dir($dir)) return [];
    $out = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4','webm','ogg','mov','m4v','jpg','jpeg','png','gif','webp'], true)) $out[] = $name;
    }
    return $out;
}

$five = adFiles(__DIR__ . '/5s');
$popup = adFiles(__DIR__ . '/popop');

function randomAd(array $files, string $base): ?array {
    if (!$files) return null;
    $name = $files[random_int(0, count($files) - 1)];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return [
        'url' => $base . '/' . rawurlencode($name),
        'type' => in_array($ext, ['mp4','webm','ogg','mov','m4v'], true) ? 'video' : 'image',
    ];
}

echo json_encode([
    'ok' => true,
    'five_seconds' => randomAd($five, '/ads/5s'),
    'popup' => randomAd($popup, '/ads/popop'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
