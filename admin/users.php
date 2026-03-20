<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Users | Admin | GoldOSRS';

// Handle balance adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid    = (int)post('user_id');
    $action = post('action');
    $amount = (int)post('amount', 0);
    $game   = post('game', 'osrs');
    if ($uid && $action === 'adjust_balance' && $amount !== 0) {
        $col = $game === 'rs3' ? 'balance_rs3' : 'balance_osrs';
        db_exec("UPDATE users SET {$col}={$col}+? WHERE id=?", 'ii', $amount, $uid);
        admin_log($admin['id'], 'balance_adjust', 'user', $uid, "{$col} += {$amount}M");
    }
    header('Location: /admin/users.php');
    exit;
}

$search = get('q', '');
$where  = $search ? 'WHERE username LIKE ? OR email LIKE ?' : '';
$params = $search ? ["%$search%", "%$search%"] : [];
$types  = $search ? 'ss' : '';
$users  = db_all("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count FROM users u {$where} ORDER BY u.created_at DESC LIMIT 100", $types, ...$params);

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
      <a href="/admin/users.php" class="active">👥 Users</a>
      <a href="/admin/gambling.php">🎲 Gambling</a>
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/admin/settings.php">⚙️ Settings</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header"><h1>Users</h1></div>
    <form method="GET" style="display:flex;gap:8px;margin-bottom:20px;max-width:400px">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search username or email…" style="flex:1;padding:9px 12px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:13px;outline:none">
      <button type="submit" class="btn-gold" style="padding:9px 16px">Search</button>
    </form>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>OSRS Bal</th><th>RS3 Bal</th><th>Orders</th><th>Role</th><th>Joined</th><th>Adjust Balance</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--text-muted);font-size:12px">#<?= $u['id'] ?></td>
          <td class="text-gold"><?= h($u['username']) ?></td>
          <td style="font-size:12px"><?= h($u['email']) ?></td>
          <td><?= fmt_gp((int)$u['balance_osrs']) ?></td>
          <td><?= fmt_gp((int)$u['balance_rs3']) ?></td>
          <td><?= (int)$u['order_count'] ?></td>
          <td><span class="status-badge <?= $u['role']==='admin'?'status-paid':'status-completed' ?>"><?= h($u['role']) ?></span></td>
          <td style="font-size:11px;color:var(--text-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <form method="POST" style="display:flex;gap:4px;align-items:center">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="adjust_balance">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="number" name="amount" placeholder="±M GP" style="width:80px;padding:4px 6px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:4px;color:var(--text);font-family:'Cinzel',serif;font-size:12px;outline:none">
              <select name="game" style="padding:4px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:4px;color:var(--text);font-family:'Cinzel',serif;font-size:11px">
                <option value="osrs">OSRS</option><option value="rs3">RS3</option>
              </select>
              <button type="submit" class="btn-gold" style="padding:4px 10px;font-size:11px">Apply</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?><tr><td colspan="9" class="text-center text-muted" style="padding:32px">No users found.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
