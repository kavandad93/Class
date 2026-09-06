(()=>{
'use strict';
const q=s=>document.querySelector(s), qa=s=>[...document.querySelectorAll(s)];
const classId=Number(document.body.dataset.kadadClassId||0);
const isManager=document.body.dataset.kadadManager==='1';
const csrf=document.body.dataset.kadadCsrf||'';
const code=new URLSearchParams(location.search).get('code')||'';
if(!classId)return;

const css=`
#kadad-requests{border-top:1px solid var(--line);background:#0a1424;min-height:0;display:grid;grid-template-rows:44px minmax(0,1fr)}
#kadad-requests-head{display:flex;align-items:center;justify-content:space-between;padding:0 12px;font:800 12px Vazirmatn;color:#fff}
#kadad-request-count{background:#d9365b;color:#fff;border-radius:999px;min-width:22px;height:22px;display:grid;place-items:center;font-size:10px}
#kadad-request-list{overflow:auto;padding:5px 8px}
.kadad-request{display:flex;gap:8px;align-items:center;padding:8px;border-radius:10px;background:#101b2e;margin-bottom:6px}
.kadad-request-info{min-width:0;flex:1}.kadad-request-info b{display:block;font:700 12px Vazirmatn;color:#fff}.kadad-request-info small{display:block;color:#91a0b8;font:500 10px Vazirmatn;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.kadad-request-actions{display:flex;gap:4px}.kadad-request-actions button{height:31px;border:0;border-radius:8px;padding:0 8px;font:800 10px Vazirmatn;cursor:pointer}.kadad-ok{background:#14532d;color:#bbf7d0}.kadad-no{background:#4c1d2a;color:#fecdd3}
#kadad-mobile-tools{display:none;position:fixed;right:12px;bottom:142px;z-index:70;gap:7px}.kadad-tool-btn{height:43px;border:1px solid var(--line);border-radius:12px;background:#111d31;color:#fff;padding:0 13px;font:800 12px Vazirmatn;cursor:pointer}
#kadad-drawer{display:none;position:fixed;inset:0;background:rgba(0,0,0,.68);z-index:100010;align-items:flex-end;justify-content:center}
#kadad-drawer .drawer-box{width:100%;height:min(84vh,760px);background:#0a1424;border:1px solid var(--line);border-radius:20px 20px 0 0;display:grid;grid-template-rows:54px 46px minmax(0,1fr);min-height:0;color:#fff}
.kadad-drawer-head{display:flex;align-items:center;justify-content:space-between;padding:0 13px;border-bottom:1px solid var(--line);font:900 14px Vazirmatn}.kadad-drawer-close{height:36px;width:36px;border:1px solid var(--line);background:#101b2e;color:#fff;border-radius:10px}
.kadad-tabs{display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--line)}.kadad-tabs button{height:46px;border:0;border-left:1px solid var(--line);background:#0d182a;color:#91a0b8;font:800 12px Vazirmatn;cursor:pointer}.kadad-tabs button.active{background:#17243a;color:#fff}.kadad-pane{display:none;min-height:0;overflow:auto;padding:10px}.kadad-pane.active{display:block}.kadad-pane .person{background:#101b2e;margin-bottom:4px}.kadad-pane .chat-message{color:#fff!important;background:#111d31!important}.kadad-mobile-chat-form{display:flex;gap:6px;position:sticky;bottom:0;background:#0a1424;padding-top:8px}.kadad-mobile-chat-form input{height:42px;flex:1;background:#111d31;border:1px solid var(--line);border-radius:10px;color:#fff;padding:0 10px;font:600 12px Vazirmatn}.kadad-mobile-chat-form button{height:42px;width:70px;border:0;border-radius:10px;background:#635bff;color:#fff;font:800 11px Vazirmatn}
#kadad-music-button{position:fixed;left:18px;top:70px;z-index:99970;display:none;height:40px;border:1px solid var(--line);border-radius:11px;background:#101b2e;color:#fff;padding:0 12px;font:800 11px Vazirmatn;cursor:pointer}
@media(max-width:900px){#kadad-mobile-tools{display:flex}.side{display:none!important}#kadad-music-button{left:10px;top:60px}.status{bottom:138px!important}}
@media(min-width:901px){.side{grid-template-rows:48px minmax(0,1fr) 210px 180px}#kadad-requests{grid-row:3}.side>.chat{grid-row:4}}
`;
const st=document.createElement('style');st.textContent=css;document.head.appendChild(st);

const safeInitial=s=>String(s||'?').trim().charAt(0)||'?';
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

let requests=[];
const requestBox=document.createElement('section');requestBox.id='kadad-requests';requestBox.innerHTML='<div id="kadad-requests-head"><span>📥 درخواست‌های ورود</span><span id="kadad-request-count">0</span></div><div id="kadad-request-list"></div>';
const side=q('.side');
if(isManager&&side)side.appendChild(requestBox);

const renderRequests=(items)=>{
 requests=items||[];
 const count=q('#kadad-request-count');if(count)count.textContent=requests.length;
 const list=q('#kadad-request-list');if(!list)return;
 if(!requests.length){list.innerHTML='<div style="padding:14px;color:#91a0b8;font:500 11px Vazirmatn;text-align:center">درخواست معلقی نیست.</div>';return;}
 list.innerHTML=requests.map(r=>'<div class="kadad-request" data-request-id="'+r.id+'"><div style="width:30px;height:30px;border-radius:9px;background:#17253b;display:grid;place-items:center;font:800 12px Vazirmatn">'+esc(safeInitial(r.name))+'</div><div class="kadad-request-info"><b>'+esc(r.name)+'</b><small>'+esc(r.email)+'</small></div><div class="kadad-request-actions"><button class="kadad-ok" data-action="approve">تأیید</button><button class="kadad-no" data-action="reject">رد</button></div></div>').join('');
};

async function loadRequests(){
 if(!isManager)return;
 try{const r=await fetch('/realtime/class_requests.php?class_id='+classId,{credentials:'same-origin',cache:'no-store'});if(!r.ok)return;const d=await r.json();if(d.ok)renderRequests(d.requests||[]);}catch(e){}
}
async function decideRequest(id,action){
 const fd=new FormData();fd.set('class_id',String(classId));fd.set('request_id',String(id));fd.set('action',action);fd.set('_csrf',csrf);
 try{const r=await fetch('/realtime/class_requests.php',{method:'POST',body:fd,credentials:'same-origin',cache:'no-store'});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'خطا');renderRequests(d.requests||[]);q('#status')&&(q('#status').textContent=action==='approve'?'درخواست تأیید شد.':'درخواست رد شد.');}catch(e){q('#status')&&(q('#status').textContent=e.message||'عملیات ناموفق بود');}
}
requestBox.addEventListener('click',e=>{const b=e.target.closest('button[data-action]'),row=e.target.closest('.kadad-request');if(b&&row)decideRequest(Number(row.dataset.requestId),b.dataset.action)});

// Mobile drawer: chat + members + requests in the same class screen.
const tools=document.createElement('div');tools.id='kadad-mobile-tools';tools.innerHTML='<button class="kadad-tool-btn" id="kadad-tools-open">☰ کلاس</button>';
document.body.appendChild(tools);
const drawer=document.createElement('div');drawer.id='kadad-drawer';drawer.innerHTML='<div class="drawer-box"><div class="kadad-drawer-head"><span>پنل کلاس</span><button class="kadad-drawer-close">✕</button></div><div class="kadad-tabs"><button data-tab="chat" class="active">💬 چت</button><button data-tab="members">👥 اعضا</button><button data-tab="requests">📥 درخواست‌ها</button></div><div class="kadad-pane active" data-pane="chat"></div><div class="kadad-pane" data-pane="members"></div><div class="kadad-pane" data-pane="requests"></div></div>';
document.body.appendChild(drawer);
const paneChat=drawer.querySelector('[data-pane="chat"]'),paneMembers=drawer.querySelector('[data-pane="members"]'),paneRequests=drawer.querySelector('[data-pane="requests"]');

function syncMobile(){
 const src=q('#mobileChatMessages')||q('#chatMessages');
 if(src){paneChat.innerHTML=src.innerHTML+'<form class="kadad-mobile-chat-form"><input id="kadad-drawer-input" maxlength="1000" placeholder="پیام..."><button>ارسال</button></form>';}
 const people=q('#participants');if(people){paneMembers.innerHTML=people.innerHTML;}
 if(isManager){paneRequests.innerHTML=q('#kadad-request-list')?.innerHTML||'<div style="padding:14px;text-align:center;color:#91a0b8">درخواستی نیست.</div>';}
}

async function sendMobileChat(){
 const input=q('#kadad-drawer-input');const msg=input?.value.trim();if(!msg)return;
 const fd=new FormData();fd.set('class_id',String(classId));fd.set('message',msg);fd.set('_csrf',csrf);
 try{const r=await fetch('/room/chat.php',{method:'POST',body:fd,credentials:'same-origin'});if(r.ok){input.value='';const rr=await fetch('/room/chat.php?class_id='+classId,{credentials:'same-origin',cache:'no-store'});const d=await rr.json();const original=q('#chatMessages');if(original&&typeof window.__kadadRenderMessages==='function')window.__kadadRenderMessages(d.messages||[]);syncMobile();}}catch(e){}
}

drawer.addEventListener('click',e=>{
 const tab=e.target.closest('.kadad-tabs button');if(tab){drawer.querySelectorAll('.kadad-tabs button').forEach(x=>x.classList.remove('active'));drawer.querySelectorAll('.kadad-pane').forEach(x=>x.classList.remove('active'));tab.classList.add('active');drawer.querySelector('[data-pane="'+tab.dataset.tab+'"]').classList.add('active');syncMobile();return;}
 if(e.target.closest('.kadad-drawer-close')||e.target===drawer){drawer.style.display='none';return;}
 const p=e.target.closest('.person[data-user-id]');if(p&&p.dataset.userId){const original=q('.person[data-user-id="'+CSS.escape(p.dataset.userId)+'"]');if(original){drawer.style.display='none';original.click();}return;}
 const b=e.target.closest('.kadad-request-actions button'),row=e.target.closest('.kadad-request');if(b&&row)decideRequest(Number(row.dataset.requestId),b.dataset.action);
});
q('#kadad-tools-open').onclick=()=>{syncMobile();drawer.style.display='flex';};

// Teacher music setting: only a URL is stored; no media file is uploaded.
let musicUrl='';
async function loadMusic(){
 if(!isManager)return;
 try{const r=await fetch('/realtime/break_settings.php?class_id='+classId,{credentials:'same-origin',cache:'no-store'});if(r.ok){const d=await r.json();musicUrl=d.music_url||'';}}catch(e){}
}
async function editMusic(){
 const value=prompt('لینک مستقیم آهنگ استراحت را وارد کنید (http/https). برای حذف، خالی بگذارید:',musicUrl);
 if(value===null)return;
 const fd=new FormData();fd.set('class_id',String(classId));fd.set('music_url',value.trim());fd.set('_csrf',csrf);
 try{const r=await fetch('/realtime/break_settings.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'خطا');musicUrl=d.music_url||'';alert(musicUrl?'موسیقی ذخیره شد.':'موسیقی حذف شد.');}catch(e){alert(e.message||'ذخیره موسیقی ناموفق بود');}
}
const waitMusicButton=setInterval(()=>{const b=q('#kadad-break-button');if(!isManager||!b)return;clearInterval(waitMusicButton);const m=document.createElement('button');m.id='kadad-music-button';m.textContent='🎵 موسیقی استراحت';m.onclick=editMusic;document.body.appendChild(m);},300);

// Break music is streamed from the URL and never uploaded to the host.
let audio=null,activeMusic='';
function syncMusic(d){
 if(!d.active){if(audio){audio.pause();audio.src='';audio=null;}activeMusic='';return;}
 const url=d.music_url||'';if(!url)return;
 if(url===activeMusic&&audio)return;
 if(audio){audio.pause();audio.src='';}
 audio=new Audio(url);audio.loop=true;audio.preload='none';audio.playsInline=true;activeMusic=url;
 audio.play().catch(()=>{const n=document.createElement('button');n.className='kadad-tool-btn';n.textContent='🔊 پخش موسیقی';n.style.position='fixed';n.style.left='10px';n.style.bottom='142px';n.style.zIndex='100011';n.onclick=()=>audio?.play().then(()=>n.remove()).catch(()=>{});document.body.appendChild(n);});
}
async function pollBreakMusic(){try{const r=await fetch('/realtime/break.php?code='+encodeURIComponent(code),{credentials:'same-origin',cache:'no-store'});if(r.ok)syncMusic(await r.json());}catch(e){}}

window.__kadadRenderMessages=window.__kadadRenderMessages||null;
setInterval(()=>{syncMobile();},2500);
loadRequests();loadMusic();pollBreakMusic();
if(isManager)setInterval(loadRequests,2000);
setInterval(pollBreakMusic,2000);
})();
