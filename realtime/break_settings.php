<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$user = require_auth();
$pdo = db();
$classId = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? 0);

$q = $pdo->prepare('SELECT id,teacher_id,break_music_url FROM classes WHERE id=? LIMIT 1');
$q->execute([$classId]);
$class = $q->fetch();
if (!$class) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'کلاس پیدا نشد'],JSON_UNESCAPED_UNICODE); exit; }
$isManager = $user['role']==='admin' || (int)$class['teacher_id']===(int)$user['id'];
if (!$isManager) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'دسترسی ندارید'],JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $url = trim((string)($_POST['music_url'] ?? ''));
    if ($url !== '') {
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower((string)($parts['scheme'] ?? '')),['http','https'],true) || empty($parts['host'])) {
            http_response_code(422); echo json_encode(['ok'=>false,'error'=>'لینک موسیقی باید یک URL معتبر http یا https باشد'],JSON_UNESCAPED_UNICODE); exit;
        }
        if (strlen($url)>1000) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'لینک خیلی طولانی است'],JSON_UNESCAPED_UNICODE); exit; }
    }
    $pdo->prepare('UPDATE classes SET break_music_url=?,updated_at=NOW() WHERE id=?')->execute([$url!==''?$url:null,$classId]);
    $class['break_music_url']=$url!==''?$url:null;
}

echo json_encode(['ok'=>true,'music_url'=>$class['break_music_url'] ?: null,'csrf'=>csrf_token()],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
