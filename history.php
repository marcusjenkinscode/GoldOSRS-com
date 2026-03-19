<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$user = require_login();
$page_title = 'Order History | GoldOSRS';
$orders = db_all('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC', 'i', $user['id']);
$games  = db_all('SELECT * FROM games  WHERE user_id=? ORDER BY created_at DESC LIMIT 50', 'i', $user['id']);
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<div class="dash-layout">
  <aside class="dash-sidebar">
    <div style="padding:20px;border-bottom:1px solid var(--border);margin-bottom:12px"><div style="font-size:13px;color:var(--text-muted)">Logged in as</div><div style="color:var(--gold);font-weight:700;font-size:16px"><?= h($user['username']) ?></div></div>
    <nav class="dash-nav">
      <a href="/dashboard.php">📊 Overview</a>
      <a href="/history.php" class="active">📋 Orders</a>
      <a href="/deposit.php">💰 Deposit</a>
      <a href="/withdraw.php">📤 Withdraw</a>
      <a href="/gambling.php">🎲 Gambling</a>
      <a href="/settings.php">⚙️ Settings</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="dash-main">
    <div class="dash-header"><h1>📋 Order History</h1></div>
    <?php if (empty($orders)): ?>
    <div class="card text-center text-muted">No orders yet. <a href="/#order" class="text-gold">Place your first order!</a></div>
    <?php else: ?>
    <div style="overflow-x:auto;margin-bottom:32px">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>Service</th><th>RSN</th><th>Amount</th><th>Price</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="text-gold" style="font-size:12px">GOS-<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></td>
          <td style="font-size:13px"><?= h($o['service_type']??$o['type']) ?></td>
          <td style="font-size:13px"><?= h($o['rsn']??'—') ?></td>
          <td><?= $o['amount']?fmt_gp((int)$o['amount']):'—' ?></td>
          <td><?= $o['price_usd']?'$'.number_format((float)$o['price_usd'],2):'—' ?></td>
          <td><span class="status-badge status-<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
          <td class="text-muted" style="font-size:12px"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($games)): ?>
    <h2 class="text-gold mb-16" style="font-size:18px">🎲 Gambling History</h2>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>Game</th><th>Bet</th><th>Result</th><th>Payout</th><th>Date</th><th>Verify</th></tr></thead>
      <tbody>
        <?php foreach ($games as $g): ?>
        <tr>
          <td><?= h(ucfirst($g['game_type'])) ?></td>
          <td><?= fmt_gp((int)$g['bet']) ?></td>
          <td class="<?= $g['won']?'feed-won':'feed-lost' ?>"><?= $g['won']?'WIN':'LOSS' ?></td>
          <td class="<?= $g['won']?'feed-won':'feed-lost' ?>"><?= $g['won']?'+'.fmt_gp((int)$g['win_amount']):'-'.fmt_gp((int)$g['bet']) ?></td>
          <td class="text-muted" style="font-size:12px"><?= date('d M H:i', strtotime($g['created_at'])) ?></td>
          <td>
            <?php if ($g['server_seed']): ?>
            <button class="btn-secondary" style="padding:3px 8px;font-size:11px" onclick="showSeeds(<?= $g['id'] ?>,`<?= h($g['server_seed']) ?>`,`<?= h($g['server_hash']) ?>`,`<?= h($g['client_seed']) ?>`,<?= $g['nonce'] ?>)">Verify</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</main>

<!-- Verify modal -->
<div id="verifyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:60000;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;max-width:500px;width:100%;padding:24px">
    <h2 class="text-gold mb-16" style="font-family:'Cinzel',serif;font-size:18px">🔍 Verify Result</h2>
    <div class="form-group"><label>Server Seed (revealed)</label><div class="btc-address" id="vServerSeed" style="font-size:11px"></div></div>
    <div class="form-group"><label>Server Hash (SHA-256)</label><div class="btc-address" id="vServerHash" style="font-size:11px"></div></div>
    <div class="form-group"><label>Client Seed</label><div class="btc-address" id="vClientSeed" style="font-size:11px"></div></div>
    <div class="form-group"><label>Nonce</label><div id="vNonce" style="color:var(--gold);font-size:14px"></div></div>
    <p class="text-muted" style="font-size:12px;margin-top:8px">Verify: <code style="color:var(--gold)">HMAC-SHA256(server_seed:client_seed:nonce)</code> → first 8 hex chars → result</p>
    <button onclick="document.getElementById('verifyModal').style.display='none'" class="btn-secondary" style="margin-top:16px;width:100%">Close</button>
  </div>
</div>
<script>
function showSeeds(id, ss, sh, cs, nonce) {
  document.getElementById('vServerSeed').textContent = ss;
  document.getElementById('vServerHash').textContent = sh;
  document.getElementById('vClientSeed').textContent = cs;
  document.getElementById('vNonce').textContent = nonce;
  document.getElementById('verifyModal').style.display = 'flex';
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
