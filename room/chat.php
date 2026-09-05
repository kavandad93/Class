<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$user=require_auth();$pdo=db();$classId=(int)($_POST['class_id']??$_GET['class_id']??0);
if($classId<1){http_response_code(400);echo json_encode(['error'=>'کلاس نامعتبر است'],JSON_UNESCAPED_UNICODE);exit;}
$q=$pdo->prepare('SELECT id,teacher_id,show_chat FROM classes WHERE id=? LIMIT 1');$q->execute([$classId]);$class=$q->fetch();if(!$class){http_response_code(404);echo json_encode(['error'=>'کلاس پیدا نشد'],JSON_UNESCAPED_UNICODE);exit;}
if(isset($class['show_chat'])&&!$class['show_chat']){http_response_code(403);echo json_encode(['error'=>'چت این کلاس توسط مدرس غیرفعال شده است'],JSON_UNESCAPED_UNICODE);exit;}
$member=$pdo->prepare('SELECT 1 FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');$member->execute([$classId,$user['id']]);$teacher=((int)$class['teacher_id']===(int)$user['id']||$user['role']==='admin');
if(!$member->fetchColumn()&&!$teacher){http_response_code(403);echo json_encode(['error'=>'دسترسی ندارید'],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){$message=trim((string)($_POST['message']??''));if($message===''||mb_strlen($message)>1000){http_response_code(422);echo json_encode(['error'=>'پیام نامعتبر است'],JSON_UNESCAPED_UNICODE);exit;}$pdo->prepare('INSERT INTO messages(class_id,user_id,message,created_at) VALUES(?,?,?,NOW())')->execute([$classId,$user['id'],$message]);}
$stmt=$pdo->prepare('SELECT m.id,m.message,m.created_at,u.name FROM messages m JOIN users u ON u.id=m.user_id WHERE m.class_id=? ORDER BY m.id DESC LIMIT 100');$stmt->execute([$classId]);$messages=array_reverse($stmt->fetchAll());echo json_encode(['messages'=>$messages],JSON_UNESCAPED_UNICODE);
