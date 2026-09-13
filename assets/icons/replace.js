(()=>{
'use strict';
const root=document.body;
const icons={
 '💬':'chat','👥':'users','📥':'requests','🎵':'music','🔊':'speaker','☰':'menu','✕':'close','📤':'send','➤':'send'
};
const esc=s=>String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const make=(name)=>{const s=document.createElement('span');s.className='kadad-svg-icon';s.innerHTML='<img src="/assets/icons/'+name+'.svg?v=20260913" alt="" aria-hidden="true">';return s;};
const css=document.createElement('style');css.textContent='.kadad-svg-icon{display:inline-flex;align-items:center;justify-content:center;width:1.15em;height:1.15em;vertical-align:-.2em;flex:0 0 auto}.kadad-svg-icon img{width:100%;height:100%;display:block;filter:none}.kadad-tool-btn,.kadad-tabs button,.kadad-drawer-close,#kadad-music-button,#kadad-music-play{display:inline-flex;align-items:center;justify-content:center;gap:7px}.kadad-svg-icon+*{margin-inline-start:0}';document.head.appendChild(css);
function replaceText(node){if(node.nodeType!==3)return;if(!node.nodeValue||!Object.keys(icons).some(x=>node.nodeValue.includes(x)))return;const frag=document.createDocumentFragment();let text=node.nodeValue;while(text){let hit=-1,key='';for(const k of Object.keys(icons)){const i=text.indexOf(k);if(i>=0&&(hit<0||i<hit)){hit=i;key=k;}}if(hit<0){frag.append(text);break;}if(hit)frag.append(document.createTextNode(text.slice(0,hit)));frag.append(make(icons[key]));text=text.slice(hit+key.length);}node.replaceWith(frag);}
function scan(){const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,{acceptNode:n=>{const p=n.parentElement;if(!p||/^(SCRIPT|STYLE|TEXTAREA|INPUT)$/.test(p.tagName))return NodeFilter.FILTER_REJECT;return NodeFilter.FILTER_ACCEPT;}});const list=[];while(walker.nextNode())list.push(walker.currentNode);list.forEach(replaceText);}
window.KadadIcons={refresh:scan};
scan();
new MutationObserver(()=>scan()).observe(root,{childList:true,subtree:true});
})();