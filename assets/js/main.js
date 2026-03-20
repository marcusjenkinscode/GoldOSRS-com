/* assets/js/main.js — GoldOSRS core JS */
'use strict';

/* ── Rune Rain ──────────────────────────────────────────────────────────────── */
(function initRunes() {
  const RUNES = 'ᚠᚢᚦᚨᚱᚲᚷᚹᚺᚾᛁᛃᛇᛈᛉᛊᛏᛒᛖᛗᛚᛜᛞᛟ'.split('');
  const container = document.getElementById('runeRain');
  if (!container) return;
  const COUNT = window.innerWidth < 768 ? 10 : 20;
  for (let i = 0; i < COUNT; i++) {
    const el = document.createElement('div');
    el.className = 'rune';
    el.textContent = RUNES[Math.floor(Math.random() * RUNES.length)];
    const left = Math.random() * 100;
    const dur  = 18 + Math.random() * 14; // 18–32 s, slow & smooth
    const delay = -(Math.random() * dur); // spread across full cycle immediately
    el.style.cssText = `left:${left}%;animation-duration:${dur}s;animation-delay:${delay}s`;
    container.appendChild(el);
  }
})();

/* ── Loading Screen ─────────────────────────────────────────────────────────── */
(function initLoader() {
  const screen = document.getElementById('loadingScreen');
  if (!screen) return;
  const hide = () => screen.classList.add('hidden');
  if (document.readyState === 'complete') { setTimeout(hide, 400); return; }
  window.addEventListener('load', () => setTimeout(hide, 400), { once: true });
  setTimeout(hide, 2200); // hard cap
})();

/* ── Navigation (mobile burger) ─────────────────────────────────────────────── */
(function initNav() {
  const burger  = document.getElementById('navBurger');
  const mobileNav = document.getElementById('mobileNav');
  const overlay = document.getElementById('navOverlay');
  if (!burger || !mobileNav) return;

  function openNav() {
    mobileNav.classList.add('open');
    overlay.classList.add('show');
    burger.classList.add('open');
    burger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeNav() {
    mobileNav.classList.remove('open');
    overlay.classList.remove('show');
    burger.classList.remove('open');
    burger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }
  burger.addEventListener('click', () => {
    mobileNav.classList.contains('open') ? closeNav() : openNav();
  });
  overlay.addEventListener('click', closeNav);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNav(); });
  window.addEventListener('resize', () => { if (window.innerWidth > 768) closeNav(); });
})();

/* ── Torch Glow ─────────────────────────────────────────────────────────────── */
(function initTorch() {
  const glow = document.getElementById('torchGlow');
  if (!glow || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  let tx = window.innerWidth / 2, ty = window.innerHeight / 2;
  let cx = tx, cy = ty;
  const LERP = 0.08;
  document.addEventListener('mousemove', e => { tx = e.clientX; ty = e.clientY; });
  (function animate() {
    cx += (tx - cx) * LERP;
    cy += (ty - cy) * LERP;
    glow.style.left = cx + 'px';
    glow.style.top  = cy + 'px';
    requestAnimationFrame(animate);
  })();

  // Click burst — spray flame particles from click point
  document.addEventListener('click', e => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    spawnFlames(e.clientX, e.clientY, 12);
  });
})();

/* ── Spark & Flame helpers ───────────────────────────────────────────────────── */
function spawnSparks(x, y, n) {
  for (let i = 0; i < n; i++) {
    const s = document.createElement('div');
    s.className = 'spark';
    const angle = Math.random() * Math.PI * 2;
    const dist  = 20 + Math.random() * 50;
    s.style.cssText = `left:${x}px;top:${y}px;--sx:${Math.cos(angle)*dist}px;--sy:${Math.sin(angle)*dist}px`;
    document.body.appendChild(s);
    setTimeout(() => s.remove(), 900);
  }
}
function spawnFlames(x, y, n) {
  for (let i = 0; i < n; i++) {
    const f = document.createElement('div');
    f.className = 'flame-burst';
    const angle  = -Math.PI/2 + (Math.random() - 0.5) * Math.PI * 1.4;
    const dist   = 10 + Math.random() * 30;
    const rotate = (Math.random() - 0.5) * 60 + 'deg';
    f.style.cssText = `left:${x}px;top:${y}px;--fx:${Math.cos(angle)*dist}px;--fy:${Math.sin(angle)*dist}px;--fr:${rotate}`;
    document.body.appendChild(f);
    setTimeout(() => f.remove(), 700);
  }
  spawnSparks(x, y, 6);
}

/* ── Toast Notifications ────────────────────────────────────────────────────── */
const Toasts = {
  container: null,
  enabled: true,
  duration: 5000,
  init() {
    this.container = document.getElementById('toast-container');
    // Read server-side settings if injected
    if (typeof TOAST_SETTINGS !== 'undefined') {
      this.enabled  = TOAST_SETTINGS.enabled !== false;
      this.duration = parseInt(TOAST_SETTINGS.duration) || 5000;
    }
    if (!this.enabled) return;
    this.fetchAndShow();
    setInterval(() => this.fetchAndShow(), 30000);
  },
  show(text, duration) {
    if (!this.enabled || !this.container) return;
    const d = duration || this.duration;
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = text;
    this.container.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.4s'; setTimeout(() => t.remove(), 400); }, d);
  },
  async fetchAndShow() {
    if (!this.enabled) return;
    try {
      const r = await fetch('/api/toasts.php');
      const d = await r.json();
      if (d.content) this.show(d.content);
    } catch (_) {}
  }
};

/* ── Live Chat ──────────────────────────────────────────────────────────────── */
const Chat = {
  sessionId: null,
  lastId: 0,
  pollInterval: null,
  guestName: null,
  open: false,

  init() {
    const fab     = document.getElementById('chatFab');
    const win     = document.getElementById('chatWindow');
    const closeBtn= document.getElementById('chatClose');
    const sendBtn = document.getElementById('chatSend');
    const input   = document.getElementById('chatInput');
    const startBtn= document.getElementById('startChatBtn');
    const footerBtn= document.getElementById('footerChatBtn');

    if (!fab) return;
    fab.addEventListener('click', () => this.toggle());
    closeBtn?.addEventListener('click', () => this.close());
    sendBtn?.addEventListener('click', () => this.sendMsg());
    input?.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMsg(); }
    });
    startBtn?.addEventListener('click', () => this.startGuestChat());
    footerBtn?.addEventListener('click', e => { e.preventDefault(); this.toggle(); });

    // Auto-start if logged in
    if (typeof SITE !== 'undefined' && SITE.loggedIn) {
      this.startUserSession();
    }
    // Handle data-open-chat links
    document.querySelectorAll('[data-open-chat]').forEach(el => {
      el.addEventListener('click', e => { e.preventDefault(); this.toggle(); });
    });
  },

  toggle() { this.open ? this.close() : this.openChat(); },

  openChat() {
    const win = document.getElementById('chatWindow');
    if (!win) return;
    win.classList.add('open');
    this.open = true;
    document.getElementById('chatBadge')?.style && (document.getElementById('chatBadge').style.display = 'none');
    document.getElementById('chatInput')?.focus();
    if (!this.sessionId && SITE?.loggedIn) this.startUserSession();
  },

  close() {
    document.getElementById('chatWindow')?.classList.remove('open');
    this.open = false;
  },

  async startGuestChat() {
    const nameEl  = document.getElementById('guestName');
    const emailEl = document.getElementById('guestEmail');
    const name = nameEl?.value.trim();
    if (!name) { nameEl?.classList.add('error'); return; }
    this.guestName = name;
    const r = await this.apiPost('/api/chat_send.php', { action: 'start', guest_name: name, guest_email: emailEl?.value.trim() });
    if (r.session_id) {
      this.sessionId = r.session_id;
      document.getElementById('chatGuestForm')?.style && (document.getElementById('chatGuestForm').style.display = 'none');
      document.getElementById('chatMessages').style.display = '';
      document.getElementById('chatInputRow').style.display = '';
      this.appendMsg('agent', 'Welcome to GoldOSRS! ⚔️ How can I help you today?');
      this.startPoll();
    }
  },

  async startUserSession() {
    const r = await this.apiPost('/api/chat_send.php', { action: 'start' });
    if (r.session_id) {
      this.sessionId = r.session_id;
      if (r.is_new) this.appendMsg('agent', 'Welcome back, ' + (SITE.username || 'adventurer') + '! ⚔️ How can I help you?');
      this.startPoll();
      this.loadHistory(r.messages || []);
    }
  },

  async sendMsg() {
    const input = document.getElementById('chatInput');
    const text = input?.value.trim();
    if (!text || !this.sessionId) return;
    input.value = '';
    this.appendMsg('user', text);
    await this.apiPost('/api/chat_send.php', { action: 'send', session_id: this.sessionId, message: text });
  },

  startPoll() {
    if (this.pollInterval) clearInterval(this.pollInterval);
    this.pollInterval = setInterval(() => this.poll(), 2500);
  },

  async poll() {
    if (!this.sessionId) return;
    try {
      const r = await fetch(`/api/chat_poll.php?session_id=${this.sessionId}&last_id=${this.lastId}`);
      const d = await r.json();
      if (d.messages?.length) {
        d.messages.forEach(m => {
          if (m.id > this.lastId) {
            this.lastId = m.id;
            if (m.sender !== 'user') {
              this.appendMsg(m.sender === 'admin' ? 'admin' : 'agent', m.message);
              if (!this.open) {
                const badge = document.getElementById('chatBadge');
                if (badge) badge.style.display = 'flex';
              }
            }
          }
        });
      }
    } catch (_) {}
  },

  appendMsg(type, text) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const msg = document.createElement('div');
    msg.className = 'chat-msg ' + type;
    msg.textContent = text;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
  },

  loadHistory(messages) {
    messages.forEach(m => {
      const type = m.sender === 'user' ? 'user' : (m.sender === 'admin' ? 'admin' : 'agent');
      this.appendMsg(type, m.message);
      if (m.id > this.lastId) this.lastId = m.id;
    });
  },

  async apiPost(url, data) {
    try {
      const formData = new FormData();
      if (typeof SITE !== 'undefined') formData.append('csrf', SITE.csrfToken);
      Object.entries(data).forEach(([k, v]) => formData.append(k, v));
      const r = await fetch(url, { method: 'POST', body: formData });
      return await r.json();
    } catch (_) { return {}; }
  }
};

/* ── Tabs ───────────────────────────────────────────────────────────────────── */
function initTabs() {
  document.querySelectorAll('.tabs').forEach(tabGroup => {
    tabGroup.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabGroup.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const panels = tabGroup.closest('section, .tabs-wrap, main')?.querySelectorAll('.tab-panel');
        panels?.forEach(p => p.classList.toggle('active', p.dataset.tab === target));
      });
    });
  });
}

/* ── Animate counters ───────────────────────────────────────────────────────── */
function initCounters() {
  const counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el  = entry.target;
      const end = parseInt(el.dataset.count);
      const dur = 1800;
      const start = performance.now();
      (function update(now) {
        const p = Math.min((now - start) / dur, 1);
        el.textContent = Math.floor(p * end).toLocaleString();
        if (p < 1) requestAnimationFrame(update);
      })(start);
      obs.unobserve(el);
    });
  }, { threshold: 0.3 });
  counters.forEach(c => obs.observe(c));
}

/* ── Copy to clipboard ──────────────────────────────────────────────────────── */
function initCopyBtns() {
  document.querySelectorAll('[data-copy]').forEach(el => {
    el.addEventListener('click', () => {
      navigator.clipboard.writeText(el.dataset.copy || el.textContent).then(() => {
        const orig = el.textContent;
        el.textContent = '✓ Copied!';
        setTimeout(() => el.textContent = orig, 2000);
      });
    });
  });
}

/* ── Payment method toggle ──────────────────────────────────────────────────── */
function initPaymentOpts() {
  document.querySelectorAll('.payment-opt').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-opt').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const input = opt.querySelector('input');
      if (input) input.checked = true;
    });
  });
}

/* ── Price calculator ───────────────────────────────────────────────────────── */
function initCalculator() {
  const amountInput = document.getElementById('calcAmount');
  const methodSelect = document.getElementById('calcMethod');
  const resultEl = document.getElementById('calcResult');
  if (!amountInput || !resultEl) return;
  function calc() {
    const amt = parseFloat(amountInput.value) || 0;
    const method = methodSelect?.value || 'osrs_crypto';
    const prices = window.GOLD_PRICES || {};
    const rate = prices[method] || 0.26;
    resultEl.textContent = '$' + (amt * rate).toFixed(2);
  }
  amountInput.addEventListener('input', calc);
  methodSelect?.addEventListener('change', calc);
  calc();
}

/* ── Init all ───────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initCounters();
  initCopyBtns();
  initPaymentOpts();
  initCalculator();
  Chat.init();
  Toasts.init();
  initScrollReveal();
  initPageEntrance();
});

/* ── Scroll Reveal ──────────────────────────────────────────────────────────── */
function initScrollReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  // Auto-tag common content blocks for reveal
  document.querySelectorAll('.card, .form-wrap, .section-header, .stat-card, .review-card, .raffle-pool-banner, .referral-block').forEach(el => {
    if (!el.classList.contains('scroll-reveal')) {
      el.classList.add('scroll-reveal');
      obs.observe(el);
    }
  });
}

/* ── Page entrance animation ────────────────────────────────────────────────── */
function initPageEntrance() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const main = document.querySelector('main');
  if (main) main.classList.add('page-enter');
}
