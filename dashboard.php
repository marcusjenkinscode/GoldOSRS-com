<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$user = require_login();

$page_title = 'Dashboard | GoldOSRS';
$page_desc  = 'Manage your GoldOSRS orders, balance and settings.';

$orders   = db_all('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 20', 'i', $user['id']);
$games    = db_all('SELECT * FROM games  WHERE user_id=? ORDER BY created_at DESC LIMIT 10', 'i', $user['id']);
$total_orders = count(db_all('SELECT id FROM orders WHERE user_id=?', 'i', $user['id']));
$total_spent  = db_one('SELECT COALESCE(SUM(price_usd),0) AS s FROM orders WHERE user_id=? AND status IN ("completed","paid")', 'i', $user['id'])['s'] ?? 0;
$total_won    = db_one('SELECT COALESCE(SUM(win_amount),0) AS s FROM games WHERE user_id=? AND won=1', 'i', $user['id'])['s'] ?? 0;

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<div class="dash-layout">
  <!-- Sidebar -->
  <aside class="dash-sidebar">
    <div style="padding:20px;border-bottom:1px solid var(--border);margin-bottom:12px">
      <div style="font-size:13px;color:var(--text-muted)">Logged in as</div>
      <div style="color:var(--gold);font-weight:700;font-size:16px"><?= h($user['username']) ?></div>
    </div>
    <nav class="dash-nav">
      <a href="/dashboard.php" class="active">📊 Overview</a>
      <a href="/history.php">📋 Order History</a>
      <a href="/deposit.php">💰 Deposit</a>
      <a href="/withdraw.php">📤 Withdraw</a>
      <a href="/gambling.php">🎲 Gambling</a>
      <a href="/settings.php">⚙️ Settings</a>
      <?php if ($user['role'] === 'admin'): ?>
      <a href="/admin/" style="color:var(--amber)">🛡️ Admin Panel</a>
      <?php endif; ?>
      <a href="/logout.php" style="margin-top:auto;color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="dash-main">
    <div class="dash-header">
      <h1>Welcome back, <?= h($user['username']) ?> ⚔️</h1>
      <p>Here's an overview of your account.</p>
    </div>

    <!-- Stats -->
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-val"><?= fmt_gp((int)$user['balance_osrs']) ?></div>
        <div class="stat-lbl">OSRS Balance</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?= fmt_gp((int)$user['balance_rs3']) ?></div>
        <div class="stat-lbl">RS3 Balance</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?= $total_orders ?></div>
        <div class="stat-lbl">Total Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-val">$<?= number_format((float)$total_spent, 2) ?></div>
        <div class="stat-lbl">Total Spent</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?= fmt_gp((int)$total_won) ?></div>
        <div class="stat-lbl">Gambling Winnings</div>
      </div>
    </div>

    <!-- Referral Program (prominent) -->
    <div class="referral-block mt-24">
      <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
        <div style="font-size:36px">🎁</div>
        <div style="flex:1;min-width:200px">
          <h3>💰 Multi-Tier Referral Program</h3>
          <p class="text-muted" style="font-size:13px;margin:6px 0 12px;line-height:1.6">
            Earn GP for every friend you refer — and every person <em>they</em> refer! Share on social media to earn even more.<br>
            <strong style="color:var(--gold)">100k OSRS</strong> or <strong style="color:var(--amber)">500k RS3</strong> per verified social share (cap: 600k OSRS / 3M RS3 per month).
          </p>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div style="background:rgba(255,215,0,0.06);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px">
              Your code: <strong class="text-gold" style="font-family:'Cinzel',serif;letter-spacing:2px"><?= h($user['referral_code'] ?? 'N/A') ?></strong>
              <button data-copy="<?= h($user['referral_code'] ?? '') ?>" style="margin-left:8px;padding:3px 8px;background:rgba(255,215,0,0.1);border:1px solid var(--border);border-radius:4px;color:var(--gold);font-size:11px;cursor:pointer;font-family:'Cinzel',serif">Copy</button>
            </div>
            <button onclick="shareReferral()" class="btn-gold" style="padding:8px 16px;font-size:12px">📤 Share &amp; Earn</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px">
      <a href="/#order" class="btn-primary">⚔️ New Order</a>
      <a href="/deposit.php" class="btn-secondary">💰 Deposit GP</a>
      <a href="/withdraw.php" class="btn-secondary">📤 Withdraw GP</a>
      <a href="/gambling.php" class="btn-secondary">🎲 Gamble</a>
    </div>

    <!-- Recent Orders -->
    <h2 class="text-gold mb-16" style="font-family:'Cinzel',serif;font-size:18px">Recent Orders</h2>
    <?php if (empty($orders)): ?>
      <div class="card text-center text-muted">No orders yet. <a href="/#order" class="text-gold">Place your first order!</a></div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>Service</th><th>Amount</th><th>Price</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="text-gold">GOS-<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></td>
          <td><?= h($o['service_type'] ?? $o['type']) ?></td>
          <td><?= $o['amount'] ? fmt_gp((int)$o['amount']) : '—' ?></td>
          <td><?= $o['price_usd'] ? '$'.number_format((float)$o['price_usd'],2) : '—' ?></td>
          <td><span class="status-badge status-<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
          <td class="text-muted" style="font-size:12px"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-16"><a href="/history.php" class="btn-secondary">View All Orders</a></div>
    <?php endif; ?>

    <!-- Recent Games -->
    <?php if (!empty($games)): ?>
    <h2 class="text-gold mb-16 mt-32" style="font-family:'Cinzel',serif;font-size:18px">Recent Gambling</h2>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>Game</th><th>Bet</th><th>Result</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($games as $g): ?>
        <tr>
          <td><?= h(ucfirst($g['game_type'])) ?></td>
          <td><?= fmt_gp((int)$g['bet']) ?></td>
          <td class="<?= $g['won'] ? 'feed-won' : 'feed-lost' ?>"><?= $g['won'] ? '+'.fmt_gp((int)$g['win_amount']) : 'Lost' ?></td>
          <td class="text-muted" style="font-size:12px"><?= date('d M H:i', strtotime($g['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</main>
<script>
function shareReferral() {
  const code = '<?= h($user['referral_code'] ?? '') ?>';
  const url  = '<?= SITE_URL ?>/register.php?ref=' + encodeURIComponent(code);
  const text = '🎮 Join GoldOSRS — The Realm\'s finest OSRS & RS3 marketplace! Use my referral code ' + code + ' to sign up: ' + url;
  if (navigator.share) {
    navigator.share({ title: 'GoldOSRS', text: text, url: url }).catch(() => {});
  } else {
    navigator.clipboard?.writeText(url).then(() => alert('Referral link copied to clipboard!\n\n' + url));
  }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
