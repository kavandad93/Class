<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_auth();

function adMediaType(string $link): string
{
    $path = (string)(parse_url($link, PHP_URL_PATH) ?: $link);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4','webm','ogg','mov','m4v'], true) ? 'video' : 'image';
}

function loadAdRegistry(): array
{
    $file = __DIR__ . '/ads.json';
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !is_array($data['ads'] ?? null)) return [];
    $out = [];
    foreach ($data['ads'] as $ad) {
        if (!is_array($ad)) continue;
        $type = (string)($ad['type'] ?? '');
        $name = trim((string)($ad['name'] ?? ''));
        $link = trim((string)($ad['link'] ?? ''));
        if (!in_array($type, ['5s', 'popup'], true) || $name === '' || $link === '') continue;
        if (preg_match('~^(https?://|/)~i', $link) !== 1) continue;
        $out[] = ['type'=>$type, 'name'=>$name, 'link'=>$link, 'media_type'=>adMediaType($link)];
    }
    return $out;
}

function randomRegisteredAd(array $ads, string $kind): ?array
{
    $matches = array_values(array_filter($ads, static fn(array $ad): bool => $ad['type'] === $kind));
    if (!$matches) return null;
    $ad = $matches[random_int(0, count($matches) - 1)];
    return ['name'=>$ad['name'], 'url'=>$ad['link'], 'type'=>$ad['media_type']];
}

$ads = loadAdRegistry();
echo json_encode([
    'ok' => true,
    'five_seconds' => randomRegisteredAd($ads, '5s'),
    'popup' => randomRegisteredAd($ads, 'popup'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
