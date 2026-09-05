<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try{
 $user=require_auth();$pdo=db();$cid=(int)($_GET['class_id']??0);if($cid<1)throw new RuntimeException('کلاس نامعتبر است.');
 $q=$pdo->prepare('SELECT c.teacher_id,cp.can_audio,cp.can_video,cp.can_screen_share FROM classes c LEFT JOIN class_participants cp ON cp.class_id=c.id AND cp.user_id=? AND cp.left_at IS NULL WHERE c.id=? LIMIT 1');$q->execute([$user['id'],$cid]);$r=$q->fetch();if(!$r)throw new RuntimeException('کلاس پیدا نشد.');
 $teacher=(int)$r['teacher_id']===(int)$user['id']||$user['role']==='admin';
 echo json_encode(['ok'=>true,'permissions'=>['audio'=>$teacher||!empty($r['can_audio']),'video'=>$teacher||!empty($r['can_video']),'screen_share'=>$teacher||!empty($r['can_screen_share']),'chat'=>true]],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
