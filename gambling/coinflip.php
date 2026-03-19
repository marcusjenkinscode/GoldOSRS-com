<?php
// gambling/coinflip.php — patched: null-safe balance reads (fixes lines 12 & 15 warnings)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();

// PATCH: guard against guest/unauthenticated access before reading balance
$user = current_user();
if (!$user) {
    // Redirect guests to login rather than crashing on null balance
    redirect('/login.php?redir=' . urlencode($_SERVER['REQUEST_URI']));
}

// PATCH: use null-coalescing so number_format never receives null
$balance_osrs = (int)($user['balance_osrs'] ?? 0);   // was: $user['balance_osrs']  → fatal on null
$balance_rs3  = (int)($user['balance_rs3']  ?? 0);   // was: $user['balance_rs3']   → fatal on null

$page_title = 'Coin Flip | GoldOSRS Gambling';
$page_desc  = 'Play provably fair coin flip with OSRS GP. Heads or tails — win 1.9x your bet.';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>⚡ Coin Flip</h1>
  <p>Heads or tails. 47.5% win chance. Win 1.9× your bet.</p>
</section>

<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="game-area">
      <!-- Balance bar — PATCH: number_format now always gets an int, never null -->
      <div class="balance-bar">
        <span class="text-muted" style="font-size:13px">OSRS Balance</span>
        <span id="userBalance" style="color:var(--gold);font-weight:700;font-size:18px">
          <?= number_format($balance_osrs) ?>M GP
        </span>
        <a href="/deposit.php" class="btn-gold" style="padding:6px 12px;font-size:12px">+ Deposit</a>
      </div>

      <div style="font-size:80px;margin:20px 0;transition:transform 0.3s" id="coinDisplay">🪙</div>
      <p class="text-muted" style="font-size:13px;margin-bottom:16px">Pick your side, place your bet</p>

      <div class="form-group" style="max-width:220px;margin:0 auto 12px">
        <label>Bet (M GP)</label>
        <input id="betAmt" type="number" value="10"
               min="5" max="<?= min($balance_osrs, 5000) ?>"
               style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none">
      </div>

      <div class="bet-quick">
        <button onclick="setBet(5)">5M</button>
        <button onclick="setBet(50)">50M</button>
        <button onclick="setBet(500)">500M</button>
        <button onclick="setBet(Math.floor(currentBal/2))">½</button>
        <button onclick="setBet(currentBal)">Max</button>
      </div>

      <div style="display:flex;gap:12px;justify-content:center;margin-top:20px">
        <button onclick="flip('heads')" class="btn-primary" style="flex:1;max-width:160px;padding:14px">
          👑 Heads
        </button>
        <button onclick="flip('tails')" class="btn-secondary" style="flex:1;max-width:160px;padding:14px">
          ⚔️ Tails
        </button>
      </div>

      <div id="flipResult" style="margin-top:20px;font-size:20px;min-height:32px;font-weight:700"></div>

      <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);font-size:12px;color:var(--text-muted)">
        Win chance: 47.5% · Payout: 1.9× · House edge: 5%
      </div>
    </div>
  </div>
</section>

<script>
const CSRF = '<?= h(csrf_token()) ?>';
let currentBal = <?= $balance_osrs ?>;

function setBet(n) {
  const input = document.getElementById('betAmt');
  if (input) input.value = Math.max(5, Math.min(n, currentBal));
}

async function flip(side) {
  const betInput = document.getElementById('betAmt');
  const bet = parseInt(betInput?.value) || 0;

  if (bet < 5)           { showResult('❌ Minimum bet is 5M GP', false); return; }
  if (bet > currentBal)  { showResult('❌ Insufficient balance!', false); return; }

  // Animate coin
  const coin = document.getElementById('coinDisplay');
  coin.textContent = '🔄';
  coin.style.animation = 'diceRoll 0.5s linear infinite';

  try {
    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('game', 'coinflip');
    fd.append('bet', bet);
    fd.append('extra', side);
    const r = await fetch('/api/game_roll.php', { method: 'POST', body: fd });
    const d = await r.json();

    setTimeout(() => {
      coin.style.animation = '';

      if (d.error) {
        coin.textContent = '🪙';
        showResult('❌ ' + d.error, false);
        return;
      }

      const isHeads = (d.result || '').includes('heads');
      coin.textContent = isHeads ? '👑' : '⚔️';

      // PATCH: safely update balance display
      if (typeof d.new_balance === 'number') {
        currentBal = d.new_balance;
        const balEl = document.getElementById('userBalance');
        if (balEl) balEl.textContent = currentBal.toLocaleString() + 'M GP';
      }

      if (d.won) {
        showResult('✅ ' + (d.result || '') + ' — Won ' + fmtGP(d.win_amount) + '!', true);
      } else {
        showResult('❌ ' + (d.result || '') + ' — Lost ' + fmtGP(bet), false);
      }
    }, 700);

  } catch (err) {
    coin.style.animation = '';
    coin.textContent = '🪙';
    showResult('❌ Network error. Try again.', false);
  }
}

function showResult(msg, won) {
  const el = document.getElementById('flipResult');
  if (!el) return;
  el.className = won ? 'result-won' : 'result-lost';
  el.textContent = msg;
}

function fmtGP(n) {
  if (!n) return '0M GP';
  return n >= 1000 ? (n / 1000).toFixed(1) + 'B GP' : n + 'M GP';
}
</script>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
