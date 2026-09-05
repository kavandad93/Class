<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_once __DIR__.'/../realtime/presets.php';

$user=require_auth();
if (!in_array($user['role'], ['teacher','admin'], true)) { http_response_code(403); exit('دسترسی ندارید.'); }
$pdo=db(); $error=null; $success=null;
function h2(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}

$cid=(int)($_GET['class_id']??$_POST['class_id']??0);
$q=$user['role']==='admin'?$pdo->prepare('SELECT * FROM classes WHERE id=? LIMIT 1'):$pdo->prepare('SELECT * FROM classes WHERE id=? AND teacher_id=? LIMIT 1');
$user['role']==='admin'?$q->execute([$cid]):$q->execute([$cid,$user['id']]);
$class=$q->fetch();
if(!$class){http_response_code(404);exit('کلاس پیدا نشد.');}

if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  verify_csrf();
  $uid=(int)$_POST['user_id'];
  $field=(string)$_POST['permission'];
  if(!in_array($field,['can_audio','can_video','can_screen_share'],true)) throw new InvalidArgumentException('مجوز نامعتبر است.');
  $value=!empty($_POST['value'])?1:0;
  $q=$pdo->prepare('SELECT id,name,email,can_audio,can_video,can_screen_share FROM class_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.class_id=? AND cp.user_id=? AND cp.role=\'student\' LIMIT 1');
  $q->execute([$cid,$uid]); $student=$q->fetch();
  if(!$student) throw new InvalidArgumentException('کاربر در این کلاس حضور ندارد.');
  $allowed=['can_audio'=>(int)$student['can_audio'],'can_video'=>(int)$student['can_video'],'can_screen_share'=>(int)$student['can_screen_share']];
  $allowed[$field]=$value;
  $apiCfg=rtk_api_config(); [$accountId,$appId,$apiToken]=$apiCfg;
  $api='https://api.cloudflare.com/client/v4/accounts/'.rawurlencode($accountId).'/realtime/kit/'.rawurlencode($appId);
  $preset=rtk_ensure_permission_preset($api,$apiToken,$allowed['can_audio'],$allowed['can_video'],$allowed['can_screen_share']);

  // Find this user's RealtimeKit participant in the class meeting by stable custom participant id.
  $participants=rtk_request('GET',$api.'/meetings/'.rawurlencode((string)$class['realtime_meeting_id']).'/participants?per_page=100',$apiToken);
  $rtkId=null;
  foreach(($participants['data']??[]) as $p){if(is_array($p)&&($p['custom_participant_id']??'')==='kadad-user-'.$uid){$rtkId=(string)$p['id'];break;}}
  if($rtkId!==null && $rtkId!=='') rtk_request('PATCH',$api.'/meetings/'.rawurlencode((string)$class['realtime_meeting_id']).'/participants/'.rawurlencode($rtkId),$apiToken,['preset_name'=>$preset]);
  $pdo->prepare("UPDATE class_participants SET can_audio=?,can_video=?,can_screen_share=?,updated_at=NOW() WHERE class_id=? AND user_id=?")->execute([$allowed['can_audio'],$allowed['can_video'],$allowed['can_screen_share'],$cid,$uid]);
  $success='مجوز «'.($field==='can_audio'?'صدا':($field==='can_video'?'تصویر':'اشتراک صفحه')).' برای «'.$student['name'].'» '.($value?'فعال':'لغو').' شد.';
 }catch(Throwable $e){$error=$e->getMessage();}
}

$q=$pdo->prepare("SELECT cp.user_id,u.name,u.email,cp.can_audio,cp.can_video,cp.can_screen_share,cp.joined_at FROM class_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.class_id=? AND cp.role='student' ORDER BY u.name");$q->execute([$cid]);$students=$q->fetchAll();
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>مجوزهای کلاس | کاداد کلاس</title><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet"><style>body{margin:0;background:#f6f7fb;color:#101828;font-family:Vazirmatn,Tahoma,sans-serif}.wrap{width:min(1000px,calc(100% - 28px));margin:30px auto}.head,.card{background:#fff;border:1px solid #e4e7ec;border-radius:18px;padding:20px;margin-bottom:16px}.head{display:flex;justify-content:space-between;gap:12px;align-items:center}.head h1{margin:0;font-size:23px}.muted{color:#667085;font-size:12px}.msg{padding:11px 13px;border-radius:11px;margin-bottom:14px;font-size:13px;font-weight:700}.ok{background:#ecfdf3;color:#027a48}.err{background:#fff1f2;color:#be123c}.table{overflow:auto}table{width:100%;border-collapse:collapse;min-width:700px}th,td{padding:12px;border-bottom:1px solid #eef0f3;text-align:right;font-size:12px}th{color:#667085}.perm{display:flex;gap:6px;flex-wrap:wrap}.perm form{margin:0}.btn{border:0;border-radius:9px;padding:8px 10px;font:700 11px Vazirmatn;cursor:pointer}.on{background:#ecfdf3;color:#027a48}.off{background:#f2f4f7;color:#667085}.back{color:#5148e8;text-decoration:none;font-size:12px;font-weight:800}</style></head><body><div class="wrap"><div class="head"><div><h1>مجوزهای رسانه‌ای کلاس</h1><div class="muted"><?=h2((string)$class['title'])?> — دانش‌آموز قبل از مجوز فقط می‌تواند کلاس را ببیند و در چت پیام بدهد.</div></div><a class="back" href="/panel/">بازگشت به پنل</a></div><?php if($success):?><div class="msg ok"><?=h2($success)?></div><?php endif;?><?php if($error):?><div class="msg err"><?=h2($error)?></div><?php endif;?><div class="card"><div class="table"><table><thead><tr><th>دانش‌آموز</th><th>صدا</th><th>تصویر</th><th>اشتراک صفحه</th></tr></thead><tbody><?php foreach($students as $s):?><tr><td><b><?=h2((string)$s['name'])?></b><div class="muted"><?=h2((string)$s['email'])?></div></td><?php foreach(['can_audio'=>'صدا','can_video'=>'تصویر','can_screen_share'=>'صفحه'] as $f=>$label):?><td><div class="perm"><form method="post"><?php csrf_field();?><input type="hidden" name="class_id" value="<?=$cid?>"><input type="hidden" name="user_id" value="<?=((int)$s['user_id'])?>"><input type="hidden" name="permission" value="<?=$f?>"><input type="hidden" name="value" value="<?=((int)$s[$f])?0:1?>"><button class="btn <?=((int)$s[$f])?'on':'off'?>" type="submit"><?=((int)$s[$f])?'✓ '.$label:'＋ '.$label?></button></form></div></td><?php endforeach;?></tr><?php endforeach;?><?php if(!$students):?><tr><td colspan="4" class="muted">هنوز دانش‌آموزی وارد این کلاس نشده است.</td></tr><?php endif;?></tbody></table></div></div></div></body></html>
