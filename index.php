<?php
declare(strict_types=1);
require_once __DIR__.'/functions.php';
$statuses = all_reflectors($REFLECTORS);
$initial = [
    'updated'=>gmdate('c'),
    'summary'=>[
        'total'=>count($statuses),
        'online'=>count(array_filter($statuses,fn($s)=>$s['online'])),
        'offline'=>count(array_filter($statuses,fn($s)=>!$s['online'])),
        'users'=>array_sum(array_map(fn($s)=>count($s['users']),$statuses)),
        'modules'=>array_sum(array_map(fn($s)=>count($s['modules']),$statuses)),
    ],
    'reflectors'=>$statuses,
    'last_heard'=>network_last_heard($statuses),
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>D-STAR Reflector Network Monitor</title>
<style>
:root{color-scheme:dark;--bg:#08111f;--panel:#101c2e;--panel2:#16243a;--line:#2b3a52;--text:#e8eef7;--muted:#94a3b8;--green:#22c55e;--red:#ef4444;--blue:#60a5fa;--amber:#f59e0b}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px/1.4 system-ui,-apple-system,Segoe UI,Arial,sans-serif}
header{padding:22px 18px;background:#0d1728;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:5}
.wrap{max-width:1600px;margin:auto;padding:0 18px}.title{font-size:28px;font-weight:800}.sub{color:var(--muted);margin-top:3px}
.stats{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:12px;margin:18px auto}.stat,.card,.panel{background:var(--panel);border:1px solid var(--line);border-radius:12px}.stat{padding:14px}.num{font-size:26px;font-weight:800}.lbl{color:var(--muted);font-size:12px}
.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0}.toolbar input,.toolbar select{background:var(--panel2);color:var(--text);border:1px solid var(--line);border-radius:8px;padding:9px 11px}.toolbar .updated{margin-left:auto;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(470px,1fr));gap:16px;padding-bottom:24px}.card{overflow:hidden}.chead{padding:15px 16px;background:var(--panel2);display:flex;justify-content:space-between;align-items:center}.rname{font-size:21px;font-weight:800}.rtype{color:var(--muted);font-size:12px}.status{font-weight:800}.on{color:var(--green)}.off{color:var(--red)}.dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px}.dgreen{background:var(--green);box-shadow:0 0 8px var(--green)}.dred{background:var(--red)}
.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:12px}.metric{background:var(--panel2);padding:9px;border-radius:8px}.metric b{display:block;font-size:16px}.metric span{font-size:11px;color:var(--muted)}
section{padding:0 12px 12px}h3{font-size:13px;margin:5px 0 8px;color:#cbd5e1;border-bottom:1px solid var(--line);padding-bottom:6px}.modules{display:flex;gap:7px;flex-wrap:wrap}.mod{background:#263650;border:1px solid #3a4b67;border-radius:7px;padding:6px 9px}.mod.active{border-color:#168a4b;background:#0b3b27}.muted{color:var(--muted)}.servicegrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.serviceitem{background:var(--panel2);border-radius:8px;padding:9px 10px}.serviceitem .label{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px}.serviceitem .value{font-weight:700;overflow-wrap:anywhere}@media(max-width:700px){.servicegrid{grid-template-columns:1fr}}
.table{overflow:auto;max-height:260px;border:1px solid var(--line);border-radius:8px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{text-align:left;padding:7px 8px;border-bottom:1px solid var(--line);white-space:nowrap}th{position:sticky;top:0;background:#17253a;color:#aebbd0}.call{font-weight:800;color:var(--blue)}a.button{display:inline-block;margin:0 12px 15px;padding:8px 11px;background:#263650;color:#fff;text-decoration:none;border-radius:7px}
.alertbar{display:none;margin:0 0 12px;padding:10px 12px;border:1px solid var(--amber);border-radius:8px;background:#3b2b08;color:#fde68a}.activity{animation:pulse 1.2s ease-out}@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(34,197,94,.65)}100%{box-shadow:0 0 0 12px rgba(34,197,94,0)}}.historygrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:8px}.historyitem{background:var(--panel2);padding:9px;border-radius:8px}.network{margin:0 0 24px}.network h2{font-size:17px;margin:0 0 10px}.footer{color:var(--muted);padding:10px 0 30px;text-align:center}
@media(max-width:700px){.stats{grid-template-columns:repeat(2,1fr)}.grid{grid-template-columns:1fr}.metrics{grid-template-columns:repeat(2,1fr)}.updated{width:100%;margin-left:0!important}}
</style>
</head>
<body>
<header><div class="wrap"><div class="title">📡 D-STAR Reflector Network Monitor</div><div class="sub">REF/DPLUS · DCS · XLX/XLXD — REF038 · REF039 · REF040 · REF049 · DCS016 · XLX038 · XLX049 · XLX139 · XLX351 · XLX978</div></div></header>
<main class="wrap">
<div id="alertbar" class="alertbar"></div>
<div class="stats" id="stats"></div>
<div class="toolbar">
<input id="filter" placeholder="Filter callsign, reflector or host…">
<select id="type"><option value="">All types</option><option>DPLUS</option><option>DCS</option><option>XLXD</option></select>
<div class="updated" id="updated">Loading…</div>
</div>
<div class="network panel" style="padding:14px">
<h2>Network-wide Last Heard</h2>
<div class="table"><table><thead><tr><th>Reflector</th><th>Callsign</th><th>Module</th><th>Time</th><th>Details</th></tr></thead><tbody id="lastheard"></tbody></table></div>
</div>
<div class="network panel" style="padding:14px"><h2>24-hour Availability History</h2><div id="history" class="historygrid"><span class="muted">Loading history…</span></div></div>
<div class="grid" id="cards"></div>
<div class="footer">Auto-refresh every <?=REFRESH_SECONDS?> seconds · Data is read from public reflector dashboards.</div>
</main>
<script>
const initial = <?=json_encode($initial,JSON_UNESCAPED_SLASHES)?>;
const REFRESH_MS = <?=REFRESH_SECONDS*1000?>;
let data = initial;
let previous = null;
let lastSuccess = Date.now();
let recentActivity = new Set();
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function heardKey(x){return [x.reflector,x.callsign,x.module,x.time].join('|');}
function detectChanges(oldData,newData){
 recentActivity=new Set(); if(!oldData) return;
 const oldMap=Object.fromEntries(oldData.reflectors.map(r=>[r.name,r]));
 for(const r of newData.reflectors){const o=oldMap[r.name];if(!o)continue;if(o.online!==r.online) notify(`${r.name} ${r.online?'recovered':'is offline'}`);}
 const oldHeard=new Set((oldData.last_heard||[]).map(heardKey));
 for(const h of (newData.last_heard||[])){if(!oldHeard.has(heardKey(h))) recentActivity.add(h.reflector);}
}
function notify(msg){if(Notification.permission==='granted') new Notification('D-STAR Monitor',{body:msg});}
function staleCheck(){const age=Math.floor((Date.now()-lastSuccess)/1000), b=document.querySelector('#alertbar');if(age>REFRESH_MS/1000*3){b.style.display='block';b.textContent=`DATA STALE — last successful update ${age} seconds ago`;}else b.style.display='none';}
async function loadHistory(){try{const r=await fetch('history_api.php?hours=24&ts='+Date.now(),{cache:'no-store'});const h=await r.json();const el=document.querySelector('#history');if(!h.enabled){el.innerHTML='<span class="muted">SQLite history is unavailable. Check php-sqlite3 and data directory permissions.</span>';return;}el.innerHTML=(h.reflectors||[]).map(x=>`<div class="historyitem"><b>${esc(x.reflector)}</b><div>${esc(x.availability_pct)}% available</div><span class="muted">${esc(x.samples)} samples · avg ${esc(x.avg_response_ms??'—')} ms</span></div>`).join('')||'<span class="muted">History will appear after samples are collected.</span>';}catch(e){document.querySelector('#history').innerHTML='<span class="muted">History unavailable.</span>';}}
function render(){
 const f=document.querySelector('#filter').value.toLowerCase(), type=document.querySelector('#type').value;
 const rs=data.reflectors.filter(r=>(!type||r.type===type)&&(!f||[r.name,r.host,r.type,...r.users.map(x=>x.callsign||'')].join(' ').toLowerCase().includes(f)));
 const online=data.summary.online;
 document.querySelector('#stats').innerHTML=[
  [''+data.summary.total,'Reflectors'],[''+online,'Online'],[''+data.summary.offline,'Offline'],[''+data.summary.users,'Reported Users'],[''+data.summary.modules,'Reported Modules']
 ].map(x=>`<div class="stat"><div class="num">${esc(x[0])}</div><div class="lbl">${x[1]}</div></div>`).join('');
 document.querySelector('#updated').textContent='Updated '+new Date(data.updated).toLocaleString();
 document.querySelector('#cards').innerHTML=rs.map(card).join('');
 document.querySelector('#lastheard').innerHTML=(data.last_heard||[]).slice(0,100).map(x=>`<tr><td>${esc(x.reflector)}</td><td class="call">${esc(x.callsign)}</td><td>${esc(x.module)}</td><td>${esc(x.time)}</td><td>${esc(x.details)}</td></tr>`).join('')||'<tr><td colspan="5" class="muted">No Last Heard data published.</td></tr>';
}
function serviceFields(r){
 const item=(label,value)=>`<div class="serviceitem"><span class="label">${esc(label)}</span><span class="value">${esc(value||'Not published')}</span></div>`;
 const state=item('Service',r.online?'ONLINE':'OFFLINE');
 const uptime=item('Uptime',r.uptime);
 if(r.type==='DPLUS') return state+uptime+item('DREFD Version',r.drefd_version);
 if(r.type==='XLXD') return state+uptime+item('XLX Version',r.xlx_version)+item('Dashboard Version',r.dashboard_version);
 if(r.type==='DCS') return state+uptime+item('DCS Version',r.dcs_version);
 return state+uptime+item('Software',r.version);
}
function card(r){
 const sortedMods=[...r.modules].sort((a,b)=>String(a.module||'').localeCompare(String(b.module||''));
 const mods=sortedMods.length?sortedMods.map(m=>`<span class="mod ${m.users>0?'active':''}"><b>${esc(m.module)}</b>${m.name?' · '+esc(m.name):''}${m.users!==null&&m.users!==undefined?' · '+esc(m.users)+' users':''}${m.links?.length?' · '+esc(m.links.join(', ')):''}</span>`).join(''):'<span class="muted">No module data published</span>';
 const users=r.users.length?r.users.slice(0,50).map(u=>`<tr>${r.type==='XLXD'?`<td>${esc(u.country||'')}</td>`:''}<td class="call">${esc(u.callsign)}</td>${r.type==='XLXD'?`<td>${esc(u.suffix||'')}</td>`:''}<td>${esc(u.module)}</td><td>${esc(u.last_heard)}</td><td>${esc(u.via||u.user||u.message||u.type)}</td></tr>`).join(''):`<tr><td colspan="${r.type==='XLXD'?6:4}" class="muted">No current user data published.</td></tr>`;
 const peers=r.peers.length?r.peers.map(p=>`<tr><td>${esc(p.peer)}</td><td>${esc(p.details)}</td></tr>`).join(''):'<tr><td colspan="2" class="muted">No peer data published.</td></tr>`;
 const thirdMetric=r.type==='DPLUS'?`<div class="metric"><b>${r.modules.length}</b><span>Available Modules</span></div>`:`<div class="metric"><b>${r.peers.length}</b><span>Peers</span></div>`;
 const userHead=r.type==='XLXD'?'<tr><th>Country / Flag</th><th>Callsign</th><th>Suffix</th><th>Module</th><th>Last Heard</th><th>Via / Peer</th></tr>':'<tr><th>Callsign</th><th>Module</th><th>Last TX / Heard</th><th>Via / User</th></tr>';
 return `<article class="card ${recentActivity.has(r.name)?'activity':''}">
 <div class="chead"><div><div class="rname">${esc(r.name)}</div><div class="rtype">${esc(r.type)} · ${esc(r.host)}</div></div><div class="status ${r.online?'on':'off'}"><span class="dot ${r.online?'dgreen':'dred'}"></span>${r.online?'ONLINE':'OFFLINE'}</div></div>
 <div class="metrics"><div class="metric"><b>${r.users.length}</b><span>${r.type==='DPLUS'?'Remote Users':'Users reported'}</span></div><div class="metric"><b>${r.modules.length}</b><span>Modules</span></div>${thirdMetric}<div class="metric"><b>${r.response_ms??'—'}${r.response_ms?' ms':''}</b><span>Response</span></div></div>
 <section><h3>Service</h3><div class="servicegrid">${serviceFields(r)}</div></section>
 <section><h3>Modules</h3><div class="modules">${mods}</div></section>
 <section><h3>${r.type==='DPLUS'?'Remote Users':'Users / Activity'}</h3><div class="table"><table><thead>${userHead}</thead><tbody>${users}</tbody></table></div></section>
 ${r.type==='DPLUS'?'':`<section><h3>Peers / Links</h3><div class="table"><table><thead><tr><th>Peer</th><th>Details</th></tr></thead><tbody>${peers}</tbody></table></div></section>`}
 <a class="button" href="${esc(r.url)}" target="_blank" rel="noopener">Open Dashboard ↗</a>
 </article>`;
}
async function refresh(){
 try{
  const r=await fetch('api.php?ts='+Date.now(),{cache:'no-store'});
  if(!r.ok) throw new Error('HTTP '+r.status);
  const next=await r.json(); detectChanges(data,next); previous=data; data=next; lastSuccess=Date.now(); render(); staleCheck();
 }catch(e){document.querySelector('#updated').textContent='Update failed: '+e.message+' · showing last data';}
}
document.querySelector('#filter').addEventListener('input',render);
document.querySelector('#type').addEventListener('change',render);
render(); loadHistory(); staleCheck();
if('Notification' in window && Notification.permission==='default') { document.addEventListener('click',()=>Notification.requestPermission(),{once:true}); }
setInterval(refresh,REFRESH_MS); setInterval(staleCheck,5000); setInterval(loadHistory,60000);
</script>
</body>
</html>
