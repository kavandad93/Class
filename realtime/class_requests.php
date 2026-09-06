<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$user = require_auth();
$pdo = db();
$classId = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$requestId = (int)($_POST['request_id'] ?? 0);

if ($classId < 1) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'شناسه کلاس نامعتبر است'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = $pdo->prepare('SELECT id,title,teacher_id FROM classes WHERE id=? LIMIT 1');
$q->execute([$classId]);
$class = $q->fetch();
if (!$class) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'کلاس پیدا نشد'], JSON_UNESCAPED_UNICODE);
    exit;
}

$isManager = $user['role'] === 'admin' || (int)$class['teacher_id'] === (int)$user['id'];
if (!$isManager) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'فقط مدرس یا مدیر دسترسی دارد'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action !== '') {
    verify_csrf();
    if ($requestId < 1) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'درخواست نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $q = $pdo->prepare('SELECT id,user_id,status FROM class_join_requests WHERE id=? AND class_id=? LIMIT 1');
    $q->execute([$requestId,$classId]);
    $request = $q->fetch();
    if (!$request) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'درخواست پیدا نشد'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'approve') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE class_join_requests SET status='approved',decided_by=?,decided_at=NOW(),reject_reason=NULL WHERE id=?")->execute([$user['id'],$requestId]);
            $pdo->prepare("INSERT INTO class_participants(class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES(?,?, 'student',NULL,NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role='student',left_at=NULL,updated_at=NOW()")->execute([$classId,$request['user_id']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } elseif ($action === 'reject') {
        $reason = trim((string)($_POST['reject_reason'] ?? ''));
        $pdo->prepare("UPDATE class_join_requests SET status='rejected',decided_by=?,decided_at=NOW(),reject_reason=? WHERE id=?")->execute([$user['id'],$reason !== '' ? mb_substr($reason,0,500) : null,$requestId]);
    } else {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'عملیات نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$q = $pdo->prepare("SELECT r.id,r.user_id,r.status,r.requested_at,u.name student_name,u.email student_email FROM class_join_requests r JOIN users u ON u.id=r.user_id WHERE r.class_id=? AND r.status='pending' ORDER BY r.requested_at ASC");
$q->execute([$classId]);
$requests = [];
foreach ($q->fetchAll() as $r) {
    $requests[] = [
        'id'=>(int)$r['id'],
        'user_id'=>(int)$r['user_id'],
        'name'=>$r['student_name'],
        'email'=>$r['student_email'],
        'requested_at'=>$r['requested_at'],
    ];
}

echo json_encode(['ok'=>true,'class_id'=>$classId,'manager'=>true,'count'=>count($requests),'requests'=>$requests,'csrf'=>csrf_token()], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
