<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();
$code = strtoupper(trim((string)($_POST['code'] ?? $_GET['code'] ?? '')));
$enter = isset($_GET['enter']) && $_GET['enter'] === '1';
$error = null;
$info = null;
$class = null;
$user = current_user();
$canEnterLive = false;

if ($code !== '') {
    if (!preg_match('/^[A-Z0-9]{4,32}$/', $code)) {
        $error = 'کد کلاس معتبر نیست.';
    } else {
        $q = $pdo->prepare('SELECT c.*,u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.room_code=? LIMIT 1');
        $q->execute([$code]);
        $class = $q->fetch();
        if (!$class) {
            $error = 'کلاسی با این کد پیدا نشد.';
        }
    }

    if ($class && !$error) {
        if ($class['expires_at'] !== null && strtotime((string)$class['expires_at']) <= time()) {
            $pdo->prepare("UPDATE classes SET status='expired' WHERE id=? AND status IN ('scheduled','active')")->execute([(int)$class['id']]);
            $error = 'زمان ورود به این کلاس تمام شده است.';
        } elseif (!in_array($class['status'], ['scheduled', 'active'], true)) {
            $error = 'این کلاس دیگر قابل ورود نیست.';
        } elseif (!$user && !empty($class['allow_guest'])) {
            // Guest access is still shown on the join screen, but the live SDK token
            // requires an authenticated participant.
            if ($enter) $error = 'برای ورود ویدئویی باید وارد حساب کاربری شوی.';
        } elseif (!$user) {
            redirect('/login/signin.php?next=' . rawurlencode('/join/?code=' . $code));
        }
    }

    if ($class && !$error && $user) {
        $isTeacher = (int)$class['teacher_id'] === (int)$user['id'] || $user['role'] === 'admin';
        if ($isTeacher) {
            $canEnterLive = true;
        } else {
            $q = $pdo->prepare('SELECT 1 FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');
            $q->execute([(int)$class['id'], (int)$user['id']]);
            $canEnterLive = (bool)$q->fetchColumn();
            if (!$canEnterLive && (bool)$pdo->query("SHOW TABLES LIKE 'class_allowed_users'")->fetch()) {
                $q = $pdo->prepare('SELECT 1 FROM class_allowed_users WHERE class_id=? AND user_id=? LIMIT 1');
                $q->execute([(int)$class['id'], (int)$user['id']);
                $canEnterLive = (bool)$q->fetchColumn();
            }
            if (!$canEnterLive && (bool)$pdo->query("SHOW TABLES LIKE 'class_join_requests'")->fetch()) {
                $q = $pdo->prepare("SELECT status FROM class_join_requests WHERE class_id=? AND user_id=? LIMIT 1");
                $q->execute([(int)$class['id'], (int)$user['id']]);
                $requestStatus = $q->fetchColumn();
                if ($requestStatus === 'approved') $canEnterLive = true;
            }
        }
        if ($enter && !$canEnterLive) {
            $error = 'دسترسی شما به این کلاس تأیید نشده است.';
            $enter = false;
        }
    }
}

if ($class && !$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $user = current_user();
        if (!$user) {
            if (!empty($class['allow_guest'])) {
                $error = 'برای ورود ویدئویی باید وارد حساب کاربری شوی.';
            } else {
                redirect('/login/signin.php?next=' . rawurlencode('/join/?code=' . $code));
            }
        } else {
            if ((int)$class['teacher_id'] === (int)$user['id'] || $user['role'] === 'admin') {
                $pdo->prepare("INSERT INTO class_participants(class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES(?,?, 'teacher',NOW(),NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role='teacher',joined_at=NOW(),left_at=NULL,updated_at=NOW()")->execute([(int)$class['id'], (int)$user['id']]);
                redirect('/join/?code=' . rawurlencode($code) . '&enter=1');
            }

            $allowed = false;
            if ((bool)$pdo->query("SHOW TABLES LIKE 'class_allowed_users'")->fetch()) {
                $q = $pdo->prepare('SELECT 1 FROM class_allowed_users WHERE class_id=? AND user_id=? LIMIT 1');
                $q->execute([(int)$class['id'], (int)$user['id']]);
                $allowed = (bool)$q->fetchColumn();
            }
            if ($allowed) {
                $pdo->prepare("INSERT INTO class_participants(class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES(?,?, 'student',NOW(),NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role='student',joined_at=NOW(),left_at=NULL,updated_at=NOW()")->execute([(int)$class['id'], (int)$user['id']]);
                redirect('/join/?code=' . rawurlencode($code) . '&enter=1');
            }

            $q = $pdo->prepare('SELECT status FROM class_join_requests WHERE class_id=? AND user_id=? LIMIT 1');
            $q->execute([(int)$class['id'], (int)$user['id']]);
            $request = $q->fetchColumn();
            if ($request === 'approved') {
                $pdo->prepare("INSERT INTO class_participants(class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES(?,?, 'student',NOW(),NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role='student',joined_at=NOW(),left_at=NULL,updated_at=NOW()")->execute([(int)$class['id'], (int)$user['id']]);
                redirect('/join/?code=' . rawurlencode($code) . '&enter=1');
            }
            if ($request === 'pending') {
                $info = 'درخواست ورودت قبلاً ارسال شده و منتظر تأیید مدرس یا مدیر است.';
            } else {
                $pdo->prepare("INSERT INTO class_join_requests(class_id,user_id,status,requested_at) VALUES(?,'?','pending',NOW())");
                $pdo->prepare("INSERT INTO class_join_requests(class_id,user_id,status,requested_at) VALUES(?,?,'pending',NOW()) ON DUPLICATE KEY UPDATE status='pending',requested_at=NOW(),decided_at=NULL,decided_by=NULL,reject_reason=NULL")->execute([(int)$class['id'], (int)$user['id']]);
                $info = 'درخواست ورود ارسال شد. بعد از تأیید مدرس یا مدیر می‌توانی وارد کلاس شوی.';
            }
        }
    } catch (Throwable $e) {
        $error = 'ارسال درخواست ورود انجام نشد. جدول درخواست‌ها را بررسی کنید.';
    }
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $class && $enter ? h((string)$class['title']).' | کاداد کلاس' : 'ورود به کلاس | کاداد کلاس' ?></title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#07101f;--panel:#0d1729;--panel2:#111d31;--line:#21304a;--text:#f6f8fc;--muted:#91a0b8;--brand:#6d63ff;--brand2:#5148e8;--danger:#ef476f;--ok:#25c58a}
*{box-sizing:border-box}body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;color:#101828;background:linear-gradient(145deg,#f8f9ff,#eef2ff);min-height:100vh}
.join-wrap{width:min(520px,100%);margin:auto;padding:24px}.brand{display:flex;justify-content:center;gap:10px;align-items:center;text-decoration:none;color:#101828;font-weight:900;font-size:22px;margin-bottom:22px}.logo{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8)}.card{background:#fff;border:1px solid #e4e7ec;border-radius:25px;padding:34px;box-shadow:0 24px 70px rgba(16,24,40,.1)}h1{margin:0 0 8px;font-size:29px}.intro{color:#667085;line-height:1.9;margin:0 0 22px}.error,.info{padding:12px 14px;border-radius:13px;margin-bottom:16px;font-size:13px;font-weight:700}.error{background:#fff1f2;color:#be123c}.info{background:#eff6ff;color:#1d4ed8}label{display:block;font-size:13px;font-weight:800;margin-bottom:7px}input{width:100%;height:54px;border:1px solid #e4e7ec;border-radius:14px;padding:0 16px;font:inherit;text-transform:uppercase;letter-spacing:2px;margin-bottom:18px}button{width:100%;height:55px;border:0;border-radius:15px;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;font:800 16px Vazirmatn;cursor:pointer}.classbox{background:#f8f9ff;border:1px solid #e4e7ec;border-radius:14px;padding:14px;margin-bottom:18px}.classbox b{font-size:16px}.classbox small{display:block;color:#667085;margin-top:5px}.back{display:block;text-align:center;margin-top:18px;color:#667085;text-decoration:none;font-size:13px}
#live{display:none;min-height:100vh;background:var(--bg);color:var(--text)}.live-app{min-height:100vh;display:grid;grid-template-rows:64px 1fr 78px}.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 20px;border-bottom:1px solid var(--line);background:#091323}.title-area{display:flex;align-items:center;gap:12px;min-width:0}.class-icon{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--brand),var(--brand2));display:grid;place-items:center;font-weight:900}.title-area strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.title-area small{display:block;color:var(--muted);margin-top:2px}.top-actions{display:flex;gap:8px}.ghost{border:1px solid var(--line);background:#101b2e;color:#dce4f1;border-radius:10px;padding:9px 13px;font:700 13px Vazirmatn;cursor:pointer}.workspace{display:grid;grid-template-columns:minmax(0,1fr) 320px;min-height:0}.stage{padding:16px;min-width:0}.video-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;height:100%;align-content:center}.tile{position:relative;min-height:220px;aspect-ratio:16/10;background:#0b1526;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,.2)}.tile video{width:100%;height:100%;display:block;object-fit:cover;background:#050a12}.tile .name{position:absolute;right:10px;bottom:10px;background:rgba(4,8,15,.72);backdrop-filter:blur(8px);padding:6px 9px;border-radius:9px;font-size:12px;font-weight:700}.avatar{position:absolute;inset:0;display:grid;place-items:center;font-size:44px;font-weight:900;color:#cdd7e8;background:radial-gradient(circle at 50% 40%,#1b2940,#0a1220)}.side{border-right:1px solid var(--line);background:#0a1424;display:grid;grid-template-rows:48px 1fr 48px;min-height:0}.side-head{padding:13px 15px;border-bottom:1px solid var(--line);font-weight:800}.participants{overflow:auto;padding:8px}.person{display:flex;align-items:center;gap:9px;padding:9px;border-radius:10px}.person:hover{background:#101d31}.person-avatar{width:32px;height:32px;border-radius:10px;background:#17253b;display:grid;place-items:center;font-weight:800}.person span{font-size:13px}.chat{border-top:1px solid var(--line);padding:8px}.chat-form{display:flex;gap:6px}.chat-form input{height:38px;margin:0;background:#111d31;border-color:var(--line);color:#fff;text-transform:none;letter-spacing:0;padding:0 10px}.chat-form button{height:38px;width:70px;font-size:12px}.messages{position:absolute;bottom:0;left:0;right:0;background:#0a1424;border-top:1px solid var(--line);max-height:300px;overflow:auto;padding:10px}.message{font-size:12px;line-height:1.7;padding:5px 0}.message b{color:#bfcaff}.toolbar{display:flex;justify-content:center;align-items:center;gap:10px;border-top:1px solid var(--line);background:#091323}.control{width:48px;height:48px;border-radius:50%;border:1px solid var(--line);background:#111d31;color:#fff;cursor:pointer;font-size:19px}.control.off{background:#3a1620;border-color:#713044}.control.active{background:#24365a}.leave{border:0;background:#d9365b;color:#fff;border-radius:13px;padding:12px 18px;font:800 13px Vazirmatn;cursor:pointer;margin-right:8px}.status{position:fixed;left:18px;bottom:94px;background:#101c30;border:1px solid var(--line);border-radius:10px;padding:8px 11px;color:#b8c5d8;font-size:12px;z-index:20}.modal{position:fixed;inset:0;background:rgba(0,0,0,.65);display:none;place-items:center;z-index:50}.modal .box{width:min(420px,calc(100% - 30px));background:#101b2d;border:1px solid var(--line);border-radius:18px;padding:20px;color:#fff}.modal input{background:#0a1424;color:#fff;border-color:var(--line);text-transform:none;letter-spacing:0}.modal .row{display:flex;gap:8px}.modal .row button{height:45px;font-size:13px}.danger{background:#d9365b!important}
@media(max-width:900px){.workspace{grid-template-columns:1fr}.side{display:none}.video-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.stage{padding:10px}.tile{min-height:190px}.topbar{padding:0 12px}.top-actions .ghost{display:none}}
</style>
</head>
<body>
<?php if ($class && $enter && !$error && $canEnterLive): ?>
<div id="live" style="display:block">
  <div class="live-app">
    <header class="topbar">
      <div class="title-area"><div class="class-icon">ک</div><div><strong><?=h((string)$class['title'])?></strong><small>مدرس: <?=h((string)$class['teacher_name'])?> · <code><?=h($code)?></code></small></div></div>
      <div class="top-actions"><button class="ghost" id="peopleBtn">👥 <span id="count">1</span></button><button class="ghost" id="fsBtn">⛶ تمام‌صفحه</button></div>
    </header>
    <main class="workspace">
      <section class="stage"><div id="videoGrid" class="video-grid"></div></section>
      <aside class="side">
        <div class="side-head">شرکت‌کنندگان <span id="sideCount">1</span></div>
        <div id="participants" class="participants"></div>
        <?php if (!isset($class['show_chat']) || $class['show_chat']): ?>
        <div class="chat"><form id="chatForm" class="chat-form"><input id="chatInput" placeholder="پیام..." maxlength="1000"><button type="submit">ارسال</button></form></div>
        <?php else: ?><div class="chat" style="color:var(--muted);font-size:12px">چت این کلاس غیرفعال است.</div><?php endif; ?>
      </aside>
    </main>
    <footer class="toolbar">
      <button id="mic" class="control active" title="میکروفون">🎙️</button>
      <button id="cam" class="control active" title="دوربین">📹</button>
      <button id="screen" class="control" title="اشتراک صفحه">🖥️</button>
      <button id="settings" class="control" title="تنظیمات">⚙️</button>
      <button id="leave" class="leave">خروج از کلاس</button>
    </footer>
  </div>
  <div id="status" class="status">در حال اتصال...</div>
</div>
<div id="settingsModal" class="modal"><div class="box"><h3 style="margin-top:0">تنظیمات دستگاه</h3><label>دوربین</label><select id="videoDevices" style="width:100%;height:44px;margin:7px 0 15px;border-radius:10px;background:#0a1424;color:#fff;border:1px solid var(--line);padding:0 10px"></select><label>میکروفون</label><select id="audioDevices" style="width:100%;height:44px;margin:7px 0 18px;border-radius:10px;background:#0a1424;color:#fff;border:1px solid var(--line);padding:0 10px"></select><div class="row"><button id="closeSettings" class="ghost" style="width:100%">بستن</button></div></div></div>
<?php else: ?>
<div class="join-wrap"><a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a><main class="card"><h1>ورود به کلاس 🎓</h1><p class="intro">کد کلاس را وارد کن؛ بعد از تأیید دسترسی، خود کلاس همین‌جا باز می‌شود.</p><?php if($error):?><div class="error"><?=h($error)?></div><?php endif;?><?php if($info):?><div class="info">✓ <?=h($info)?></div><?php endif;?><?php if($class&&!$error&&!$info):?><div class="classbox"><b><?=h((string)$class['title'])?></b><small>مدرس: <?=h((string)$class['teacher_name'])?> · کد: <?=h((string)$class['room_code'])?></small></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><label>کد کلاس</label><input name="code" value="<?=h($code)?>" minlength="4" maxlength="32" required autofocus><button><?= $class ? 'ادامه و ورود به کلاس' : 'پیدا کردن کلاس' ?></button></form></main><a class="back" href="/panel">← بازگشت به پنل</a></div>
<?php endif; ?>
<?php if ($class && $enter && !$error && $canEnterLive): ?>
<script src="https://cdn.jsdelivr.net/npm/@cloudflare/realtimekit@latest/dist/browser.js"></script>
<script>
(async()=>{
 const classId=<?= (int)$class['id'] ?>, grid=document.getElementById('videoGrid'), people=document.getElementById('participants'), status=document.getElementById('status');
 let meeting=null, known=new Map(), localVideoTrack=null;
 const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
 const initial=s=>String(s||'?').trim().charAt(0)||'?';
 function setStatus(s){status.textContent=s}
 function tile(id,name,local){
   let el=document.querySelector('[data-peer="'+CSS.escape(id)+'"]');
   if(!el){el=document.createElement('div');el.className='tile';el.dataset.peer=id;el.innerHTML='<div class="avatar">'+esc(initial(name))+'</div><video autoplay playsinline></video><div class="name">'+esc(name)+(local?' · شما':'')+'</div>';grid.appendChild(el)}
   return el;
 }
 function attach(el,participant){
   const v=el.querySelector('video'), tracks=[];
   if(participant.videoTrack) tracks.push(participant.videoTrack);
   if(participant.audioTrack) tracks.push(participant.audioTrack);
   if(tracks.length){const old=v.srcObject; const ids=tracks.map(t=>t.id).join('|'); if(!v.dataset.tracks||v.dataset.tracks!==ids){v.srcObject=new MediaStream(tracks);v.dataset.tracks=ids;v.play().catch(()=>{});}}
   el.querySelector('.avatar').style.display=participant.videoTrack&&participant.videoEnabled?'none':'grid';
 }
 function render(){
   if(!meeting)return;
   const list=[meeting.self,...meeting.participants.joined.toArray()];
   const ids=new Set();
   for(const p of list){const id=String(p.id||p.userId||Math.random()), name=p.name||'کاربر';ids.add(id);const el=tile(id,name,p===meeting.self);attach(el,p);known.set(id,p);}
   grid.querySelectorAll('[data-peer]').forEach(el=>{if(!ids.has(el.dataset.peer))el.remove()});
   people.innerHTML=list.map(p=>'<div class="person"><div class="person-avatar">'+esc(initial(p.name))+'</div><span>'+esc(p.name||'کاربر')+(p===meeting.self?' (شما)':'')+'</span></div>').join('');
   document.getElementById('count').textContent=list.length;document.getElementById('sideCount').textContent=list.length;
 }
 async function toggleAudio(){if(meeting.self.audioEnabled)await meeting.self.disableAudio();else await meeting.self.enableAudio();document.getElementById('mic').classList.toggle('off',!meeting.self.audioEnabled);}
 async function toggleVideo(){if(meeting.self.videoEnabled)await meeting.self.disableVideo();else await meeting.self.enableVideo();document.getElementById('cam').classList.toggle('off',!meeting.self.videoEnabled);render();}
 try{
   const r=await fetch('/realtime/token.php?class_id='+classId,{credentials:'same-origin'}); const d=await r.json(); if(!r.ok||!d.ok) throw new Error(d.error||'دریافت دسترسی کلاس ناموفق بود.');
   meeting=await RealtimeKitClient.init({authToken:d.token,defaults:{audio:true,video:true}}); await meeting.join();
   setStatus('متصل به کلاس'); render();
   meeting.self.on('audioUpdate',render); meeting.self.on('videoUpdate',render); meeting.self.on('screenShareUpdate',render);
   meeting.participants.joined.on('audioUpdate',render); meeting.participants.joined.on('videoUpdate',render); meeting.participants.joined.on('screenShareUpdate',render);
   setInterval(render,1000);
   document.getElementById('mic').onclick=toggleAudio; document.getElementById('cam').onclick=toggleVideo;
   document.getElementById('screen').onclick=async()=>{try{if(meeting.self.screenShareEnabled)await meeting.self.disableScreenShare();else await meeting.self.enableScreenShare();render();}catch(e){setStatus('اشتراک صفحه در این مرورگر در دسترس نیست.')}};
   document.getElementById('leave').onclick=async()=>{try{await meeting.leave()}finally{location.href='/join/?code='+encodeURIComponent('<?=h($code)?>')}};
   document.getElementById('fsBtn').onclick=()=>document.documentElement.requestFullscreen?.();
   document.getElementById('settings').onclick=async()=>{const m=document.getElementById('settingsModal');m.style.display='grid';const a=await meeting.self.getAudioDevices(),v=await meeting.self.getVideoDevices();document.getElementById('audioDevices').innerHTML=a.map(x=>'<option value="'+esc(x.deviceId)+'">'+esc(x.label||'میکروفون')+'</option>').join('');document.getElementById('videoDevices').innerHTML=v.map(x=>'<option value="'+esc(x.deviceId)+'">'+esc(x.label||'دوربین')+'</option>').join('');};
   document.getElementById('closeSettings').onclick=()=>document.getElementById('settingsModal').style.display='none';
   document.getElementById('audioDevices').onchange=async e=>{const ds=await meeting.self.getAudioDevices();const d=ds.find(x=>x.deviceId===e.target.value);if(d)await meeting.self.setDevice(d)};
   document.getElementById('videoDevices').onchange=async e=>{const ds=await meeting.self.getVideoDevices();const d=ds.find(x=>x.deviceId===e.target.value);if(d)await meeting.self.setDevice(d)};
   <?php if (!isset($class['show_chat']) || $class['show_chat']): ?>
   const form=document.getElementById('chatForm'), input=document.getElementById('chatInput');
   async function loadChat(){try{const r=await fetch('/room/chat.php?class_id='+classId,{credentials:'same-origin'});const d=await r.json(); if(!d.messages)return; const old=document.getElementById('chatMessages'); if(old)old.remove(); const box=document.createElement('div');box.id='chatMessages';box.className='messages';box.style.display='none';box.innerHTML=d.messages.map(m=>'<div class="message"><b>'+esc(m.name)+'</b>: '+esc(m.message)+'</div>').join('');document.querySelector('.side').appendChild(box);}}
   form?.addEventListener('submit',async e=>{e.preventDefault();const msg=input.value.trim();if(!msg)return;const fd=new FormData();fd.append('class_id',classId);fd.append('message',msg);await fetch('/room/chat.php',{method:'POST',body:fd,credentials:'same-origin'});input.value='';loadChat();});
   <?php endif; ?>
 }catch(e){setStatus('خطا'); alert(e.message||e); location.href='/join/?code='+encodeURIComponent('<?=h($code)?>');}
})();
</script>
<?php endif; ?>
</body></html>
