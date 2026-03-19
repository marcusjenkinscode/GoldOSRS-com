<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();

$page_title = 'Admin Dashboard | GoldOSRS';

$stats = [
    'users'      => db_one('SELECT COUNT(*) AS c FROM users WHERE role="user"')['c'] ?? 0,
    'orders'     => db_one('SELECT COUNT(*) AS c FROM orders')['c'] ?? 0,
    'pending'    => db_one('SELECT COUNT(*) AS c FROM orders WHERE status="pending"')['c'] ?? 0,
    'revenue'    => db_one('SELECT COALESCE(SUM(price_usd),0) AS s FROM orders WHERE status IN ("paid","completed")')['s'] ?? 0,
    'chats_open' => db_one('SELECT COUNT(*) AS c FROM chat_sessions WHERE status="open"')['c'] ?? 0,
    'games_today'=> db_one('SELECT COUNT(*) AS c FROM games WHERE DATE(created_at)=CURDATE()')['c'] ?? 0,
];

$recent_orders = db_all('SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 10');
$recent_users  = db_all('SELECT * FROM users ORDER BY created_at DESC LIMIT 5');

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <h2>🛡️ Admin Panel</h2>
    <nav class="admin-nav">
      <a href="/admin/" class="active">📊 Dashboard</a>
      <a href="/admin/chat.php">💬 Live Chat</a>
      <a href="/admin/orders.php">📋 Orders</a>
      <a href="/admin/users.php">👥 Users</a>
      <a href="/admin/gambling.php">🎲 Gambling</a>
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header"><h1>Dashboard</h1><p>Welcome back, <?= h($admin['username']) ?></p></div>
    <div class="stat-cards">
      <div class="stat-card"><div class="stat-val"><?= $stats['users'] ?></div><div class="stat-lbl">Total Users</div></div>
      <div class="stat-card"><div class="stat-val"><?= $stats['orders'] ?></div><div class="stat-lbl">Total Orders</div></div>
      <div class="stat-card" style="border-color:rgba(255,165,0,0.4)"><div class="stat-val" style="color:#ffa500"><?= $stats['pending'] ?></div><div class="stat-lbl">Pending Orders</div></div>
      <div class="stat-card"><div class="stat-val">$<?= number_format((float)$stats['revenue'],2) ?></div><div class="stat-lbl">Total Revenue</div></div>
      <div class="stat-card" style="<?= $stats['chats_open'] > 0 ? 'border-color:rgba(39,174,96,0.4)' : '' ?>"><div class="stat-val" style="<?= $stats['chats_open'] > 0 ? 'color:#27ae60' : '' ?>"><?= $stats['chats_open'] ?></div><div class="stat-lbl">Open Chats</div></div>
      <div class="stat-card"><div class="stat-val"><?= $stats['games_today'] ?></div><div class="stat-lbl">Games Today</div></div>
    </div>
    <?php if ($stats['chats_open'] > 0): ?>
    <div style="background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px">
      💬 <strong><?= $stats['chats_open'] ?> open chat(s)</strong> waiting for reply — <a href="/admin/chat.php" class="text-gold">Go to Live Chat →</a>
    </div>
    <?php endif; ?>
    <h2 class="text-gold mb-16" style="font-size:17px">Recent Orders</h2>
    <div style="overflow-x:auto;margin-bottom:28px">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>User</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($recent_orders as $o): $ref = 'GOS-'.str_pad($o['id'],6,'0',STR_PAD_LEFT); ?>
        <tr>
          <td class="text-gold"><?= h($ref) ?></td>
          <td><?= h($o['username'] ?? $o['guest_rsn'] ?? '—') ?></td>
          <td><?= h($o['service_type'] ?? $o['type']) ?></td>
          <td><?= $o['amount'] ? fmt_gp((int)$o['amount']) : ($o['price_usd'] ? '$'.$o['price_usd'] : '—') ?></td>
          <td><span class="status-badge status-<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
          <td><a href="/admin/orders.php?id=<?= $o['id'] ?>" class="btn-secondary" style="padding:4px 10px;font-size:11px">Manage</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div style="display:flex;gap:12px"><a href="/admin/orders.php" class="btn-secondary">All Orders</a><a href="/admin/chat.php" class="btn-secondary">Live Chat</a></div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
