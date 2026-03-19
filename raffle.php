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
        <div class="card" style="max-width:480px;margin:0 auto;text-align:center">
          <div style="font-size:40px;margin-bottom:12px">🎟️</div>
          <h3>Enter This Week's Draw</h3>
          <p class="text-muted" style="margin:12px 0 20px">Place an order of any amount to earn raffle tickets automatically. 1 ticket per order!</p>
          <a href="/#order" class="btn-primary">⚔️ Place an Order</a>
        </div>
      <?php else: ?>
        <div class="card" style="max-width:480px;margin:0 auto;text-align:center">
          <div style="font-size:40px;margin-bottom:12px">🎟️</div>
          <h3>Login to Enter the Raffle</h3>
          <p class="text-muted" style="margin:12px 0 20px">Create an account and place an order to earn raffle tickets!</p>
          <div style="display:flex;gap:10px;justify-content:center">
            <a href="/login.php" class="btn-primary">Login</a>
            <a href="/register.php" class="btn-secondary">Register</a>
          </div>
        </div>
      <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
