<?php
declare(strict_types=1);

if (str_starts_with((string)($_SERVER['REQUEST_URI'] ?? ''), '/join/') && (($_GET['enter'] ?? '') === '1')) {
    ob_start(static function (string $html): string {
        if (stripos($html, '</body>') === false) return $html;
        $scripts = '<script src="/ads/player.js"></script><script src="/join/class_features.js"></script><script src="/join/permissions_live.js"></script>';
        return preg_replace('~</body>~i', $scripts . '</body>', $html, 1) ?? $html;
    });
}
