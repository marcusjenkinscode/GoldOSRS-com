<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Gambling | Admin | GoldOSRS';

// Toggle gambling on/off
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $enabled = post('gambling_enabled', '0');
    db_exec("INSERT INTO config (`key`, value) VALUES ('gambling_enabled', ?) ON DUPLICATE KEY UPDATE value=?", 'ss', $enabled, $enabled);
    admin_log($admin['id'], 'gambling_' . ($enabled === '1' ? 'enabled' : 'disabled'));
    header('Location: /admin/gambling.php');
    exit;
}

$stats = [
    'total_games'  => db_one('SELECT COUNT(*) AS c FROM games')['c'] ?? 0,
    'total_bets'   => db_one('SELECT COALESCE(SUM(bet),0) AS s FROM games')['s'] ?? 0,
    'total_won'    => db_one('SELECT COALESCE(SUM(win_amount),0) AS s FROM games WHERE won=1')['s'] ?? 0,
    'house_profit' => 0,
    'today_games'  => db_one('SELECT COUNT(*) AS c FROM games WHERE DATE(created_at)=CURDATE()')['c'] ?? 0,
    'winners'      => db_one('SELECT COUNT(*) AS c FROM games WHERE won=1')['c'] ?? 0,
];
$stats['house_profit'] = $stats['total_bets'] - $stats['total_won'];

$by_type = db_all('SELECT game_type, COUNT(*) AS cnt, SUM(bet) AS total_bet, SUM(CASE WHEN won=1 THEN win_amount ELSE 0 END) AS total_won FROM games GROUP BY game_type');
$recent  = db_all('SELECT g.*, u.username FROM games g JOIN users u ON u.id=g.user_id ORDER BY g.created_at DESC LIMIT 20');
$gambling_enabled = get_config('gambling_enabled', '1');

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <h2>🛡️ Admin Panel</h2>
    <nav class="admin-nav">
      <a href="/admin/">📊 Dashboard</a>
      <a href="/admin/chat.php">💬 Live Chat</a>
      <a href="/admin/orders.php">📋 Orders</a>
      <a href="/admin/users.php">👥 Users</a>
      <a href="/admin/gambling.php" class="active">🎲 Gambling</a>
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header" style="display:flex;justify-content:space-between;align-items:flex-start">
      <div><h1>Gambling Stats</h1></div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="gambling_enabled" value="<?= $gambling_enabled==='1'?'0':'1' ?>">
        <button type="submit" class="<?= $gambling_enabled==='1'?'btn-secondary':'btn-primary' ?>" style="padding:10px 20px">
          <?= $gambling_enabled==='1'?'🔒 Disable Gambling':'🟢 Enable Gambling' ?>
        </button>
      </form>
    </div>

    <div class="stat-cards">
      <div class="stat-card"><div class="stat-val"><?= number_format($stats['total_games']) ?></div><div class="stat-lbl">Total Games</div></div>
      <div class="stat-card"><div class="stat-val"><?= fmt_gp((int)$stats['total_bets']) ?></div><div class="stat-lbl">Total Wagered</div></div>
      <div class="stat-card"><div class="stat-val"><?= fmt_gp((int)$stats['total_won']) ?></div><div class="stat-lbl">Total Paid Out</div></div>
      <div class="stat-card" style="<?= $stats['house_profit']>0?'border-color:rgba(39,174,96,0.4)':'' ?>">
        <div class="stat-val" style="color:<?= $stats['house_profit']>0?'#27ae60':'#e74c3c' ?>"><?= fmt_gp((int)abs($stats['house_profit'])) ?></div>
        <div class="stat-lbl">House <?= $stats['house_profit']>=0?'Profit':'Loss' ?></div>
      </div>
      <div class="stat-card"><div class="stat-val"><?= $stats['today_games'] ?></div><div class="stat-lbl">Games Today</div></div>
      <div class="stat-card"><div class="stat-val"><?= $stats['total_games']>0?round($stats['winners']/$stats['total_games']*100,1).'%':'—' ?></div><div class="stat-lbl">Win Rate</div></div>
    </div>

    <h2 class="text-gold mb-16 mt-24" style="font-size:16px">By Game Type</h2>
    <div style="overflow-x:auto;margin-bottom:28px">
    <table class="data-table">
      <thead><tr><th>Game</th><th>Games</th><th>Total Wagered</th><th>Paid Out</th><th>House Edge</th></tr></thead>
      <tbody>
        <?php foreach ($by_type as $r):
          $edge = $r['total_bet'] > 0 ? round(($r['total_bet']-$r['total_won'])/$r['total_bet']*100,2) : 0;
        ?>
        <tr>
          <td class="text-gold"><?= h(ucfirst($r['game_type'])) ?></td>
          <td><?= number_format($r['cnt']) ?></td>
          <td><?= fmt_gp((int)$r['total_bet']) ?></td>
          <td><?= fmt_gp((int)$r['total_won']) ?></td>
          <td style="color:<?= $edge>0?'#27ae60':'#e74c3c' ?>"><?= $edge ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <h2 class="text-gold mb-16" style="font-size:16px">Recent Games</h2>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>User</th><th>Game</th><th>Bet</th><th>Result</th><th>Date</th><th>Seeds</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $g): ?>
        <tr>
          <td><?= h($g['username']) ?></td>
          <td><?= h(ucfirst($g['game_type'])) ?></td>
          <td><?= fmt_gp((int)$g['bet']) ?></td>
          <td class="<?= $g['won']?'feed-won':'feed-lost' ?>"><?= $g['won']?'+'.fmt_gp((int)$g['win_amount']):'Lost' ?></td>
          <td style="font-size:11px;color:var(--text-muted)"><?= date('d M H:i', strtotime($g['created_at'])) ?></td>
          <td style="font-size:10px;color:var(--text-muted)">Hash: <?= h(substr($g['server_hash']??'',0,12)) ?>…</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
