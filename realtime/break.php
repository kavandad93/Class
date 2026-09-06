<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$user = require_auth();
$pdo = db();
$code = strtoupper(trim((string)($_POST['code'] ?? $_GET['code'] ?? '')));
$action = (string)($_POST['action'] ?? '');

if (!preg_match('/^[A-Z0-9]{4,32}$/', $code)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'کد کلاس نامعتبر است'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = $pdo->prepare('SELECT id,teacher_id,status,break_active,break_started_at,break_ad_name,break_music_url FROM classes WHERE room_code=? LIMIT 1');
$q->execute([$code]);
$class = $q->fetch();
if (!$class) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'کلاس پیدا نشد'], JSON_UNESCAPED_UNICODE);
    exit;
}

$classId = (int)$class['id'];
$isManager = $user['role'] === 'admin' || (int)$class['teacher_id'] === (int)$user['id'];
$member = $pdo->prepare('SELECT 1 FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');
$member->execute([$classId,(int)$user['id']]);
if (!$isManager && !$member->fetchColumn()) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'دسترسی ندارید'], JSON_UNESCAPED_UNICODE);
    exit;
}

function registryFiveSecondAds(): array
{
    $file = __DIR__ . '/../ads/ads.json';
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !is_array($data['ads'] ?? null)) return [];
    $out = [];
    foreach ($data['ads'] as $ad) {
        if (!is_array($ad) || ($ad['type'] ?? '') !== '5s') continue;
        $name = trim((string)($ad['name'] ?? ''));
        $link = trim((string)($ad['link'] ?? ''));
        if ($name === '' || $link === '' || preg_match('~^(https?://|/)~i',$link) !== 1) continue;
        $path = (string)(parse_url($link,PHP_URL_PATH) ?: $link);
        $ext = strtolower(pathinfo($path,PATHINFO_EXTENSION));
        if (in_array($ext,['mp4','webm','ogg','mov','m4v','jpg','jpeg','png','gif','webp'],true)) {
            $out[] = ['name'=>$name,'url'=>$link,'type'=>in_array($ext,['mp4','webm','ogg','mov','m4v'],true)?'video':'image'];
        }
    }
    return $out;
}

if ($action !== '') {
    if (!$isManager) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'فقط مدرس یا مدیر می‌تواند زمان استراحت را کنترل کند'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    verify_csrf();
    if ($action === 'start') {
        $ads = registryFiveSecondAds();
        $ad = $ads ? $ads[random_int(0,count($ads)-1)] : null;
        $pdo->prepare('UPDATE classes SET break_active=1,break_started_at=NOW(),break_ad_name=? WHERE id=?')->execute([$ad['name'] ?? null,$classId]);
    } elseif ($action === 'end') {
        $pdo->prepare('UPDATE classes SET break_active=0,break_started_at=NULL,break_ad_name=NULL WHERE id=?')->execute([$classId]);
    } else {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'عملیات نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $q=$pdo->prepare('SELECT break_active,break_started_at,break_ad_name,break_music_url FROM classes WHERE id=? LIMIT 1');
    $q->execute([$classId]);
    $class=array_merge($class,$q->fetch()?:[]);
}

$ad=null;
if (!empty($class['break_ad_name'])) {
    foreach (registryFiveSecondAds() as $candidate) {
        if ($candidate['name'] === $class['break_ad_name']) {$ad=$candidate;break;}
    }
}

$music=null;
if (!empty($class['break_music_url'])) {
    $parts=parse_url((string)$class['break_music_url']);
    if ($parts && in_array(strtolower((string)($parts['scheme']??'')),['http','https'],true) && !empty($parts['host'])) $music=(string)$class['break_music_url'];
}

echo json_encode([
    'ok'=>true,
    'class_id'=>$classId,
    'manager'=>$isManager,
    'csrf'=>csrf_token(),
    'active'=>(bool)$class['break_active'],
    'started_at'=>$class['break_started_at'],
    'ad'=>$ad,
    'music_url'=>$music,
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
