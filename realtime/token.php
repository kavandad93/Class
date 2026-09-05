<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/presets.php';
header('Content-Type: application/json; charset=utf-8');

function cloudflare_error_message(string $raw, int $status): string
{
    $data=json_decode($raw,true);$parts=[];
    foreach(($data['errors']??[]) as $err){if(is_array($err)&&!empty($err['message']))$parts[]=(isset($err['code'])?'['.$err['code'].'] ':'').$err['message'];}
    if(!$parts&&is_array($data)&&isset($data['message']))$parts[]=(string)$data['message'];
    return 'HTTP '.$status.'.'.($parts?' '.implode(' | ',$parts):'');
}
function cloudflare_get(string $url,string $apiToken):array{return rtk_request('GET',$url,$apiToken);}

try{
 $user=require_auth();$pdo=db();$classId=(int)($_GET['class_id']??$_POST['class_id']??0);if($classId<1)throw new RuntimeException('کلاس نامعتبر است.');
 $q=$pdo->prepare('SELECT c.*,u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.id=? LIMIT 1');$q->execute([$classId]);$class=$q->fetch();if(!$class)throw new RuntimeException('کلاس پیدا نشد.');
 if($class['expires_at']!==null&&strtotime((string)$class['expires_at'])<=time())throw new RuntimeException('زمان کلاس تمام شده است.');
 if(!in_array($class['status'],['scheduled','active'],true))throw new RuntimeException('این کلاس قابل ورود نیست.');
 $isTeacher=(int)$class['teacher_id']===(int)$user['id']||$user['role']==='admin';$allowed=false;$media=['can_audio'=>0,'can_video'=>0,'can_screen_share'=>0];
 if($isTeacher){$allowed=true;}else{
  $q=$pdo->prepare('SELECT can_audio,can_video,can_screen_share FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');$q->execute([$classId,$user['id']]);$cp=$q->fetch();
  if($cp){$allowed=true;$media=['can_audio'=>(int)$cp['can_audio'],'can_video'=>(int)$cp['can_video'],'can_screen_share'=>(int)$cp['can_screen_share']];}
  if(!$allowed&&(bool)$pdo->query("SHOW TABLES LIKE 'class_allowed_users'")->fetch()){$q=$pdo->prepare('SELECT 1 FROM class_allowed_users WHERE class_id=? AND user_id=? LIMIT 1');$q->execute([$classId,$user['id']]);$allowed=(bool)$q->fetchColumn();}
 }
 if(!$allowed)throw new RuntimeException('دسترسی شما به این کلاس تأیید نشده است.');
 [$accountId,$appId,$apiToken]=rtk_api_config();$api='https://api.cloudflare.com/client/v4/accounts/'.rawurlencode($accountId).'/realtime/kit/'.rawurlencode($appId);
 $meetingId=trim((string)($class['realtime_meeting_id']??''));
 if($meetingId===''){
  $data=rtk_request('POST',$api.'/meetings',$apiToken,['title'=>$class['title']]);$meetingId=(string)($data['data']['id']??'');if($meetingId==='')throw new RuntimeException('شناسه Meeting از Cloudflare دریافت نشد.');
  $pdo->prepare('UPDATE classes SET realtime_meeting_id=?,updated_at=NOW() WHERE id=?')->execute([$meetingId,$classId]);
 }
 if($isTeacher){
  $configured=trim((string)(require '/home2/kadad/data.php')['realtimekit_preset']??'');
  if($configured==='')$configured='group_call_host';
  $presetName=$configured;
 }else{
  $presetName=rtk_ensure_permission_preset($api,$apiToken,$media['can_audio'],$media['can_video'],$media['can_screen_share']);
 }
 $body=['custom_participant_id'=>'kadad-user-'.$user['id'],'preset_name'=>$presetName,'name'=>(string)$user['name']];
 $data=rtk_request('POST',$api.'/meetings/'.rawurlencode($meetingId).'/participants',$apiToken,$body);$token=(string)($data['data']['token']??'');$participantId=(string)($data['data']['id']??'');
 if($token==='')throw new RuntimeException('توکن RealtimeKit دریافت نشد.');
 if(!$isTeacher&&$participantId!==''){
  $pdo->prepare('UPDATE class_participants SET updated_at=NOW() WHERE class_id=? AND user_id=?')->execute([$classId,$user['id']]);
 }
 echo json_encode(['ok'=>true,'token'=>$token,'meeting_id'=>$meetingId,'permissions'=>['audio'=>(bool)$media['can_audio'],'video'=>(bool)$media['can_video'],'screen_share'=>(bool)$media['can_screen_share'],'chat'=>true]],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
