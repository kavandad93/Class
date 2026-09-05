<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$rawMode = strtolower(trim((string)($_SERVER['QUERY_STRING'] ?? '')));
$mode = $rawMode === 'popup' ? 'popup' : (preg_match('/^(\d+)s-gif$/', $rawMode, $m) ? 'gif' : 'gif');
$requestedSeconds = isset($m[1]) ? max(1, min(120, (int)$m[1])) : 5;

try {
    $stmt = db()->prepare("SELECT id,title,image_url,url,duration FROM advertisements WHERE active=1 AND image_url IS NOT NULL AND image_url<>'' ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $ad = $stmt->fetch();
} catch (Throwable $e) {
    $ad = null;
}

if (!$ad) {
    http_response_code(204);
    exit;
}

$src = (string)$ad['image_url'];
if (!preg_match('~^https?://~i', $src) && $src[0] !== '/') {
    $src = '/ads/' . ltrim($src, '/');
}
$duration = $mode === 'gif' ? $requestedSeconds : max(1, min(120, (int)$ad['duration']));

try {
    $u = current_user();
    db()->prepare("INSERT INTO ad_events(advertisement_id,class_id,user_id,event,shown_at) VALUES(?,NULL,?,'shown',NOW())")
        ->execute([(int)$ad['id'], $u ? (int)$u['id'] : null]);
} catch (Throwable $e) {
    // An ad must still render if analytics logging fails.
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h((string)$ad['title'])?></title>
<style>
html,body{margin:0;width:100%;height:100%;font-family:Tahoma,sans-serif;background:transparent;overflow:hidden}.ad{position:relative;width:100%;height:100%;display:grid;place-items:center}.ad a{display:block;max-width:100%;max-height:100%}.ad img{display:block;max-width:100%;max-height:100%;object-fit:contain}.timer{position:absolute;top:8px;left:8px;background:rgba(0,0,0,.7);color:#fff;border-radius:999px;padding:5px 9px;font-size:11px}.popup{position:fixed;inset:0;background:rgba(0,0,0,.48);display:grid;place-items:center;padding:20px}.popup-card{position:relative;background:#fff;border-radius:18px;padding:10px;max-width:min(560px,95vw);max-height:90vh}.popup-card img{display:block;max-width:100%;max-height:78vh;object-fit:contain}.close{position:absolute;top:-10px;left:-10px;width:32px;height:32px;border:0;border-radius:50%;background:#111;color:#fff;cursor:pointer;font-size:18px}
</style>
</head>
<body>
<?php if ($mode === 'popup'): ?>
<div class="popup" id="popup"><div class="popup-card"><button class="close" onclick="closeAd()" aria-label="بستن">×</button><a href="<?=h((string)($ad['url'] ?: '#'))?>" target="_blank" rel="noopener noreferrer"><img src="<?=h($src)?>" alt="<?=h((string)$ad['title'])?>"></a></div></div>
<script>function closeAd(){document.getElementById('popup').remove();}</script>
<?php else: ?>
<div class="ad"><span class="timer" id="timer"><?=h((string)$duration)?>s</span><a href="<?=h((string)($ad['url'] ?: '#'))?>" target="_blank" rel="noopener noreferrer"><img src="<?=h($src)?>" alt="<?=h((string)$ad['title'])?>"></a></div>
<script>let left=<?=json_encode($duration)?>;const t=document.getElementById('timer');const i=setInterval(()=>{left--;if(left<=0){clearInterval(i);t.textContent='';}else t.textContent=left+'s';},1000);</script>
<?php endif; ?>
</body></html>
