<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Prices | Admin | GoldOSRS';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $prices = db_all('SELECT `key` FROM prices');
    foreach ($prices as $p) {
        $val = post($p['key']);
        if ($val !== '' && is_numeric($val)) {
            db_exec('UPDATE prices SET value=? WHERE `key`=?', 'ds', (float)$val, $p['key']);
        }
    }
    admin_log($admin['id'], 'prices_updated');
    $saved = true;
}

$all_prices = db_all('SELECT * FROM prices ORDER BY id');
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
      <a href="/admin/gambling.php">🎲 Gambling</a>
      <a href="/admin/prices.php" class="active">💰 Prices</a>
      <a href="/admin/settings.php">⚙️ Settings</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header"><h1>Price Editor</h1><p>Changes go live immediately sitewide.</p></div>
    <?php if ($saved): ?><div style="background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#27ae60">✅ Prices saved successfully.</div><?php endif; ?>
    <form method="POST" class="form-wrap" style="max-width:600px">
      <?= csrf_field() ?>
      <?php foreach ($all_prices as $p): ?>
      <div class="form-group">
        <label><?= h($p['label'] ?? $p['key']) ?></label>
        <input type="number" name="<?= h($p['key']) ?>" value="<?= h($p['value']) ?>" step="0.0001" min="0">
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn-primary btn-full">💾 Save Prices</button>
    </form>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
