(()=>{
'use strict';
const classIdFromCode=()=>new URLSearchParams(location.search).get('code')||'';
let previous=null;
async function poll(){const code=classIdFromCode();if(!code)return;try{const r=await fetch('/realtime/my_permissions.php?class_id='+encodeURIComponent(new URLSearchParams(location.search).get('class_id')||''),{credentials:'same-origin',cache:'no-store'});if(!r.ok)return;const d=await r.json();if(!d.ok)return;apply(d.permissions||{});}catch(e){}}
function apply(p){const map=[['audio','mic'],['video','cam'],['screen_share','screen']];map.forEach(([key,id])=>{const b=document.getElementById(id);if(!b)return;const allowed=!!p[key];b.disabled=!allowed;b.title=allowed?(key==='audio'?'میکروفون':key==='video'?'دوربین':'اشتراک صفحه')+' فعال است':'این مجوز توسط مدرس فعال نیست';b.style.opacity=allowed?'1':'.38';b.style.pointerEvents=allowed?'auto':'none';if(previous&&previous[key]===false&&allowed){b.click?.();}});previous={...p};}
// class_id is not in the public URL, so read it from the live page once it exposes it.
const getClassId=async()=>{const code=classIdFromCode();if(!code)return 0;try{const r=await fetch('/realtime/break.php?code='+encodeURIComponent(code),{credentials:'same-origin',cache:'no-store'});const d=await r.json();return d.class_id||0;}catch(e){return 0;}};
(async()=>{const id=await getClassId();if(!id)return;window.__kadadPermissionClassId=id;const run=async()=>{try{const r=await fetch('/realtime/my_permissions.php?class_id='+id,{credentials:'same-origin',cache:'no-store'});if(!r.ok)return;const d=await r.json();if(d.ok)apply(d.permissions||{});}catch(e){}};await run();setInterval(run,2000);})();
})();
