<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$user = require_auth();
$classId = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
if ($classId < 1) { http_response_code(400); echo json_encode(['error'=>'کلاس نامعتبر است'], JSON_UNESCAPED_UNICODE); exit; }
$member = db()->prepare('SELECT 1 FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');
$member->execute([$classId,$user['id']]);
$teacher = db()->prepare('SELECT 1 FROM classes WHERE id=? AND teacher_id=? LIMIT 1');
$teacher->execute([$classId,$user['id']]);
if (!$member->fetchColumn() && !$teacher->fetchColumn()) { http_response_code(403); echo json_encode(['error'=>'دسترسی ندارید'], JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '' || mb_strlen($message) > 1000) { http_response_code(422); echo json_encode(['error'=>'پیام نامعتبر است'], JSON_UNESCAPED_UNICODE); exit; }
    db()->prepare('INSERT INTO messages (class_id,user_id,message,created_at) VALUES (?,?,?,NOW())')->execute([$classId,$user['id'],$message]);
}
$stmt = db()->prepare('SELECT m.id,m.message,m.created_at,u.name FROM messages m JOIN users u ON u.id=m.user_id WHERE m.class_id=? ORDER BY m.id DESC LIMIT 100');
$stmt->execute([$classId]);
$messages = array_reverse($stmt->fetchAll());
$stmt = db()->prepare('SELECT COUNT(*) FROM class_participants WHERE class_id=? AND left_at IS NULL');
$stmt->execute([$classId]);
echo json_encode(['messages'=>$messages,'participants'=>(int)$stmt->fetchColumn()], JSON_UNESCAPED_UNICODE);
