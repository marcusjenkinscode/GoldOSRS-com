<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Live Chat Admin | GoldOSRS';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <h2>🛡️ Admin Panel</h2>
    <nav class="admin-nav">
      <a href="/admin/">📊 Dashboard</a>
      <a href="/admin/chat.php" class="active">💬 Live Chat</a>
      <a href="/admin/orders.php">📋 Orders</a>
      <a href="/admin/users.php">👥 Users</a>
      <a href="/admin/gambling.php">🎲 Gambling</a>
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/admin/settings.php">⚙️ Settings</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main" style="padding:0;display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 72px);position:relative">

    <!-- Sessions sidebar — desktop inline / mobile slide-out -->
    <div id="sessionsPanel" class="admin-sessions-panel" style="position:relative;top:auto;left:auto;transform:none;border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden">
      <div style="padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span style="font-family:'Cinzel',serif;color:var(--gold);font-weight:700">💬 Active Chats <span id="sessionCount" style="color:var(--text-muted);font-size:12px"></span></span>
        <button id="closeSessions" onclick="toggleSessions()" style="display:none;background:none;border:none;color:var(--text-muted);font-size:18px;cursor:pointer" aria-label="Close">✕</button>
      </div>
      <div id="sessionList" style="overflow-y:auto;flex:1;padding:8px"></div>
    </div>

    <!-- Mobile toggle button -->
    <button class="admin-sessions-toggle" id="sessionToggleBtn" onclick="toggleSessions()" title="Toggle chat list" aria-label="Toggle sessions">💬</button>

    <!-- Chat area -->
    <div style="display:flex;flex-direction:column;overflow:hidden">
      <div id="chatHeader" style="padding:14px 20px;border-bottom:1px solid var(--border);background:var(--bg-card2);min-height:52px">
        <span style="color:var(--text-muted);font-size:14px">← Select a chat session</span>
      </div>
      <div id="adminMessages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px"></div>
      <div style="padding:12px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:flex-end">
        <textarea id="adminInput" placeholder="Type reply… (Enter to send)" style="flex:1;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:13px;resize:none;height:48px;outline:none" maxlength="2000"></textarea>
        <button id="adminSend" class="btn-gold" style="height:48px;padding:0 20px">Send</button>
        <button id="adminClose" class="btn-secondary" style="height:48px;padding:0 16px;font-size:12px">Close Chat</button>
      </div>
      <!-- Quick replies -->
      <div style="padding:8px 12px;border-top:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap">
        <?php
        $quick = ["Hi! How can I help you today? ⚔️","Your order is being processed!","Please trade to our account: [RSN]","Payment confirmed ✅ — processing now!","Your order is complete! Thank you 🏆","What RSN and world please?"];
        foreach ($quick as $q): ?>
        <button class="quick-reply" data-msg="<?= h($q) ?>" style="padding:4px 10px;border-radius:4px;background:rgba(255,215,0,0.08);border:1px solid var(--border);color:var(--gold);font-size:11px;cursor:pointer;font-family:'Cinzel',serif"><?= h(mb_substr($q,0,25)).'…' ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</main>

<script>
const CSRF = '<?= h(csrf_token()) ?>';
let activeSession = null;
let lastMsgId = 0;
let pollTimer = null;

async function api(action, extra = {}) {
  const fd = new FormData();
  fd.append('csrf', CSRF);
  fd.append('action', action);
  if (activeSession) fd.append('session_id', activeSession);
  Object.entries(extra).forEach(([k,v]) => fd.append(k,v));
  const r = await fetch('/api/admin_reply.php', { method:'POST', body:fd });
  return r.json();
}

async function loadSessions() {
  const d = await api('sessions');
  const list = document.getElementById('sessionList');
  const sessions = d.sessions || [];
  document.getElementById('sessionCount').textContent = `(${sessions.filter(s=>s.status==='open').length} open)`;
  list.innerHTML = sessions.map(s => `
    <div class="session-item" data-id="${s.id}" onclick="selectSession(${s.id},'${(s.username||s.guest_name||'Guest').replace(/'/g,"\\'")}',${s.unread})"
      style="padding:12px;border-radius:8px;cursor:pointer;margin-bottom:4px;background:${activeSession==s.id?'rgba(255,215,0,0.08)':'transparent'};border:1px solid ${activeSession==s.id?'var(--border-hover)':'transparent'};transition:all 0.15s">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <strong style="font-size:13px;color:${s.status==='open'?'var(--gold)':'var(--text-muted)'}">${escHtml(s.username||s.guest_name||'Guest')}</strong>
        ${s.unread>0?`<span style="background:#e74c3c;color:#fff;border-radius:10px;padding:2px 7px;font-size:10px;font-weight:700">${s.unread}</span>`:''}
      </div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">#${s.id} · ${s.status} · ${s.ip||''}</div>
      <div style="font-size:11px;color:var(--text-muted)">${timeSince(s.last_activity)}</div>
    </div>`).join('');
}

async function selectSession(id, name, unread) {
  activeSession = id; lastMsgId = 0;
  document.getElementById('chatHeader').innerHTML = `<strong style="color:var(--gold)">${escHtml(name)}</strong> <span style="color:var(--text-muted);font-size:12px">· Session #${id}</span>`;
  document.getElementById('adminMessages').innerHTML = '';
  const d = await api('messages', { session_id: id });
  (d.messages||[]).forEach(m => appendMsg(m));
  if (d.messages?.length) lastMsgId = d.messages[d.messages.length-1].id;
  clearInterval(pollTimer);
  pollTimer = setInterval(pollMessages, 2500);
  loadSessions();
}

async function pollMessages() {
  if (!activeSession) return;
  const fd = new FormData();
  const r = await fetch(`/api/chat_poll.php?session_id=${activeSession}&last_id=${lastMsgId}`);
  const d = await r.json();
  (d.messages||[]).forEach(m => { appendMsg(m); lastMsgId = m.id; });
}

function appendMsg(m) {
  const box = document.getElementById('adminMessages');
  const div = document.createElement('div');
  div.className = 'chat-msg ' + (m.sender==='user'?'user':'admin');
  div.style.alignSelf = m.sender==='user'?'flex-start':'flex-end';
  div.textContent = (m.sender_name?m.sender_name+': ':'')+m.message;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
}

async function sendReply() {
  const inp = document.getElementById('adminInput');
  const msg = inp.value.trim();
  if (!msg || !activeSession) return;
  inp.value = '';
  const d = await api('reply', { message: msg });
  if (d.success) appendMsg({ sender:'admin', sender_name:'<?= h($admin['username']) ?>', message: msg });
}

document.getElementById('adminSend').onclick = sendReply;
document.getElementById('adminInput').addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendReply();} });
document.getElementById('adminClose').onclick = async () => {
  if (!activeSession || !confirm('Close this chat session?')) return;
  await api('close_session');
  activeSession = null; lastMsgId = 0; clearInterval(pollTimer);
  document.getElementById('chatHeader').innerHTML = '<span style="color:var(--text-muted)">← Select a chat</span>';
  document.getElementById('adminMessages').innerHTML = '';
  loadSessions();
};
document.querySelectorAll('.quick-reply').forEach(btn => {
  btn.onclick = () => { document.getElementById('adminInput').value = btn.dataset.msg; document.getElementById('adminInput').focus(); }
});

function escHtml(s) { const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
function timeSince(ts) { const s=Math.floor((Date.now()-new Date(ts+'Z').getTime())/1000); if(s<60)return s+'s ago'; if(s<3600)return Math.floor(s/60)+'m ago'; return Math.floor(s/3600)+'h ago'; }

// Mobile sessions panel toggle
function toggleSessions() {
  const panel  = document.getElementById('sessionsPanel');
  const btn    = document.getElementById('sessionToggleBtn');
  const close  = document.getElementById('closeSessions');
  const open   = panel.classList.toggle('open');
  if (btn)   btn.style.background   = open ? 'var(--amber)' : 'var(--gold)';
  if (close) close.style.display    = open ? '' : 'none';
  document.body.style.overflow = open ? 'hidden' : '';
}
// On mobile (<900px) switch panel to slide-out mode
function applyMobileLayout() {
  const panel = document.getElementById('sessionsPanel');
  const close = document.getElementById('closeSessions');
  if (!panel) return;
  if (window.innerWidth <= 900) {
    panel.style.cssText = ''; // let CSS class handle it
    if (close) close.style.display = panel.classList.contains('open') ? '' : 'none';
  } else {
    // Desktop: always visible inline
    panel.classList.remove('open');
    panel.style.cssText = 'border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;position:relative;top:auto;left:auto;transform:none;width:auto;height:auto;z-index:auto';
    if (close) close.style.display = 'none';
    document.body.style.overflow = '';
  }
}
window.addEventListener('resize', applyMobileLayout);
applyMobileLayout();

loadSessions();
setInterval(loadSessions, 5000);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
