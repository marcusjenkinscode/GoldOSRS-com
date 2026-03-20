<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();

$page_title = 'OSRS Raffle — Win GP & Prizes | GoldOSRS';
$page_desc  = 'Enter the GoldOSRS raffle to win OSRS gold, RS3 gold, and exclusive prizes. New draws every week!';

// Fetch prizes (suppress error if table not yet created)
$prizes    = db_all('SELECT * FROM raffle_prizes ORDER BY value DESC') ?: [];
$prize_pool = array_sum(array_column($prizes, 'value'));

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>🎁 OSRS Raffle</h1>
  <p>Win OSRS GP, RS3 Gold &amp; exclusive prizes. New draw every week!</p>
</section>

<section class="section" style="padding-top:20px">
  <div class="container">

    <!-- Prize Pool Banner -->
    <div class="raffle-pool-banner" id="poolBanner">
      <div class="raffle-pool-label">💰 Current Prize Pool</div>
      <div class="raffle-pool-value" id="prizePoolVal">
        <?= fmt_gp((int)$prize_pool) ?>
      </div>
      <div class="raffle-pool-sub"><?= count($prizes) ?> prize<?= count($prizes) !== 1 ? 's' : '' ?> available · Draw every Friday</div>
    </div>

    <!-- Chest -->
    <div class="raffle-chest-wrap">
      <div id="raffleChest" class="raffle-chest" onclick="toggleInventory()" title="Click to reveal prizes">
        <div class="chest-body">🎁</div>
        <div class="chest-shine"></div>
      </div>
      <div class="raffle-chest-hint" id="chestHint">Click the chest to reveal prizes</div>
    </div>

    <!-- Prize Inventory (hidden by default) -->
    <div id="prizeInventory" class="raffle-inventory" style="display:none">
      <h2 class="section-title" style="font-size:22px;margin-bottom:20px">🏆 Prize Inventory</h2>
      <?php if (empty($prizes)): ?>
        <div class="text-muted text-center" style="padding:24px">
          Prizes are being added — check back soon! 🎁
        </div>
      <?php else: ?>
      <div class="grid-3">
        <?php foreach ($prizes as $prize): ?>
        <div class="card raffle-prize-card">
          <div class="card-icon">🏆</div>
          <h3><?= h($prize['name']) ?></h3>
          <div class="raffle-prize-value">
            <?= fmt_gp((int)$prize['value']) ?>
          </div>
          <div class="raffle-prize-date text-muted" style="font-size:12px;margin-top:8px">
            Added <?= date('d M Y', strtotime($prize['added_date'])) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Enter Raffle CTA -->
    <div class="text-center mt-40">
      <?php if (is_logged_in()): ?>
        <?php
        $user_tickets = (int)(db_one('SELECT COALESCE(SUM(tickets),0) AS t FROM raffle_entries WHERE user_id=?', 'i', current_user()['id'])['t'] ?? 0);
        ?>
        <div class="grid-3" style="max-width:800px;margin:0 auto">
          <div class="card" style="text-align:center">
            <div style="font-size:36px;margin-bottom:10px">🎟️</div>
            <h3>Earn Tickets Automatically</h3>
            <p class="text-muted" style="margin:10px 0 16px;font-size:13px">Every order you place automatically earns raffle tickets — the more you spend, the more tickets!</p>
            <div style="font-size:12px;color:var(--text-muted);text-align:left;padding:0 8px">
              <div style="padding:4px 0;border-bottom:1px solid var(--border)">🪙 Under $10 → 1 ticket</div>
              <div style="padding:4px 0;border-bottom:1px solid var(--border)">💰 $10–$49 → 3 tickets</div>
              <div style="padding:4px 0;border-bottom:1px solid var(--border)">💎 $50–$99 → 8 tickets</div>
              <div style="padding:4px 0">👑 $100+ → 20 tickets</div>
            </div>
            <a href="/#order" class="btn-gold mt-16" style="width:100%;display:block">⚔️ Place an Order</a>
          </div>
          <div class="card" style="text-align:center">
            <div style="font-size:36px;margin-bottom:10px">🏆</div>
            <h3>Your Tickets</h3>
            <div style="font-family:'Cinzel Decorative',serif;font-size:48px;color:var(--gold);margin:12px 0"><?= $user_tickets ?></div>
            <p class="text-muted" style="font-size:13px;margin-bottom:16px">Tickets entered this draw</p>
            <?php if ($user_tickets > 0): ?><div class="promo-badge">✅ You're in this week's draw!</div>
            <?php else: ?><p class="text-muted" style="font-size:12px">Place an order or buy tickets to enter</p>
            <?php endif; ?>
          </div>
          <div class="card" style="text-align:center">
            <div style="font-size:36px;margin-bottom:10px">💫</div>
            <h3>Buy Extra Tickets</h3>
            <p class="text-muted" style="font-size:13px;margin:10px 0 16px">Purchase additional raffle tickets using your GP balance.</p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
              <button class="ticket-option" onclick="buyTickets(1,5)" style="display:flex;justify-content:space-between;padding:10px 14px">
                <span><strong class="text-gold">1 ticket</strong></span><span class="text-muted" style="font-size:12px">5M GP</span>
              </button>
              <button class="ticket-option" onclick="buyTickets(5,20)" style="display:flex;justify-content:space-between;padding:10px 14px">
                <span><strong class="text-gold">5 tickets</strong></span><span class="text-muted" style="font-size:12px">20M GP <span style="color:#27ae60;font-size:10px">save 5M</span></span>
              </button>
              <button class="ticket-option" onclick="buyTickets(15,50)" style="display:flex;justify-content:space-between;padding:10px 14px">
                <span><strong class="text-gold">15 tickets</strong></span><span class="text-muted" style="font-size:12px">50M GP <span style="color:#27ae60;font-size:10px">best value</span></span>
              </button>
            </div>
            <div style="font-size:11px;color:var(--text-muted)">Balance: <?= fmt_gp((int)current_user()['balance_osrs']) ?></div>
          </div>
        </div>
      <?php else: ?>
        <div class="card" style="max-width:480px;margin:0 auto;text-align:center">
          <div style="font-size:40px;margin-bottom:12px">🎟️</div>
          <h3>Login to Enter the Raffle</h3>
          <p class="text-muted" style="margin:12px 0 20px">Create an account and place an order to earn raffle tickets automatically!</p>
          <div style="display:flex;gap:10px;justify-content:center">
            <a href="/login.php" class="btn-primary">Login</a>
            <a href="/register.php" class="btn-secondary">Register</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Weekly Tournament -->
    <div class="card mt-32" style="max-width:700px;margin:32px auto;text-align:center">
      <div style="font-size:36px;margin-bottom:12px">🏆</div>
      <h3 style="font-family:'Cinzel Decorative',serif;color:var(--gold);margin-bottom:8px">Weekly Tournament</h3>
      <p class="text-muted" style="font-size:14px;margin-bottom:16px;line-height:1.6">New tournament every <strong style="color:var(--gold)">Friday</strong>. Rules and events are announced in our Discord server.<br>
      Prizes are <strong style="color:var(--gold)">tiered</strong>: 🥇 1st Place &nbsp;·&nbsp; 🥈 2nd Place &nbsp;·&nbsp; 🥉 3rd Place</p>
      <a href="https://discord.gg/n9HP7GH2e3" target="_blank" rel="noopener" class="btn-gold">🎮 Join Discord for Rules &amp; Events</a>
    </div>

  </div>
</section>
</main>

<style>
/* ── Raffle page styles ───────────────────────────────── */
.raffle-pool-banner {
  text-align: center;
  background: linear-gradient(135deg, rgba(255,215,0,0.08), rgba(255,140,0,0.05));
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 32px 24px;
  margin-bottom: 40px;
}
.raffle-pool-label { font-size: 14px; color: var(--text-muted); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
.raffle-pool-value { font-family: 'Cinzel Decorative', serif; font-size: clamp(36px, 7vw, 72px); color: var(--gold); text-shadow: 0 0 30px rgba(255,215,0,0.4); }
.raffle-pool-sub   { font-size: 13px; color: var(--text-muted); margin-top: 8px; }

.raffle-chest-wrap { display: flex; flex-direction: column; align-items: center; margin: 0 0 40px; }
.raffle-chest {
  font-size: 100px;
  cursor: pointer;
  transition: transform 0.25s cubic-bezier(.34,1.56,.64,1), filter 0.25s;
  filter: drop-shadow(0 0 20px rgba(255,215,0,0.3));
  user-select: none;
  position: relative;
}
.raffle-chest:hover { transform: scale(1.08); filter: drop-shadow(0 0 32px rgba(255,215,0,0.6)); }
.raffle-chest.open  { transform: scale(1.15) rotate(-5deg); animation: chestBounce 0.5s ease; }
@keyframes chestBounce {
  0%   { transform: scale(1); }
  40%  { transform: scale(1.25) rotate(-8deg); }
  70%  { transform: scale(1.1)  rotate(4deg); }
  100% { transform: scale(1.15) rotate(-5deg); }
}
.raffle-chest-hint { font-size: 13px; color: var(--text-muted); margin-top: 12px; transition: opacity 0.3s; }
.raffle-chest.open + .raffle-chest-hint { opacity: 0; }

.raffle-inventory { animation: fadeSlideDown 0.4s ease; }
@keyframes fadeSlideDown {
  from { opacity: 0; transform: translateY(-12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.raffle-prize-card { text-align: center; }
.raffle-prize-value { font-family: 'Cinzel Decorative', serif; font-size: 22px; color: var(--gold); margin-top: 8px; }
</style>

<script>
let inventoryOpen = false;

function toggleInventory() {
  const chest = document.getElementById('raffleChest');
  const inv   = document.getElementById('prizeInventory');
  inventoryOpen = !inventoryOpen;
  chest.classList.toggle('open', inventoryOpen);
  if (inventoryOpen) {
    inv.style.display = '';
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    inv.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
  } else {
    inv.style.display = 'none';
  }
}
</script>

<?php if (is_logged_in()): ?>
<!-- Buy tickets modal -->
<div id="ticketModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:60000;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;max-width:380px;width:100%;padding:28px;text-align:center">
    <div style="font-size:40px;margin-bottom:12px">🎟️</div>
    <h3 class="text-gold" id="ticketModalTitle">Buy Tickets</h3>
    <p class="text-muted" id="ticketModalDesc" style="margin:10px 0 20px;font-size:14px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button id="ticketConfirm" class="btn-primary">Confirm Purchase</button>
      <button onclick="document.getElementById('ticketModal').style.display='none'" class="btn-secondary">Cancel</button>
    </div>
    <div id="ticketResult" style="margin-top:16px;font-size:14px"></div>
  </div>
</div>
<script>
const RAFFLE_CSRF = '<?= h(csrf_token()) ?>';
function buyTickets(qty, costM) {
  document.getElementById('ticketModalTitle').textContent = `Buy ${qty} ticket${qty>1?'s':''}`;
  document.getElementById('ticketModalDesc').textContent  = `Cost: ${costM}M OSRS GP`;
  document.getElementById('ticketResult').textContent     = '';
  document.getElementById('ticketModal').style.display    = 'flex';
  document.getElementById('ticketConfirm').onclick = async () => {
    const btn = document.getElementById('ticketConfirm');
    btn.disabled = true; btn.textContent = 'Processing…';
    const fd = new FormData();
    fd.append('csrf', RAFFLE_CSRF); fd.append('qty', qty); fd.append('cost_m', costM);
    try {
      const r = await fetch('/api/raffle_buy.php', { method:'POST', body: fd });
      const d = await r.json();
      const res = document.getElementById('ticketResult');
      res.style.color = d.success ? '#27ae60' : '#e74c3c';
      res.textContent = d.message || d.error || 'Error';
      if (d.success) setTimeout(() => { document.getElementById('ticketModal').style.display='none'; location.reload(); }, 1500);
    } catch(e) {
      document.getElementById('ticketResult').textContent = 'Request failed. Please try again.';
    }
    const btn2 = document.getElementById('ticketConfirm');
    btn2.disabled = false; btn2.textContent = 'Confirm Purchase';
  };
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
