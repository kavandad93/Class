<?php
declare(strict_types=1);

if (str_starts_with((string)($_SERVER['REQUEST_URI'] ?? ''), '/join/') && (($_GET['enter'] ?? '') === '1')) {
    ob_start(static function (string $html): string {
        if (stripos($html, 'id="live"') === false || stripos($html, '</body>') === false) return $html;
        $script = '<script src="/ads/player.js"></script>';
        return preg_replace('~</body>~i', $script . '</body>', $html, 1) ?? $html;
    });
}
