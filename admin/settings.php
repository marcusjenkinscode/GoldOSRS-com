<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Settings | Admin | GoldOSRS';
$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'toast') {
        $enabled  = isset($_POST['toasts_enabled']) ? '1' : '0';
        $duration = max(1000, min(30000, (int)post('toast_duration_ms', '5000')));
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('toasts_enabled',?) ON DUPLICATE KEY UPDATE `value`=?", 'ss', $enabled, $enabled);
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('toast_duration_ms',?) ON DUPLICATE KEY UPDATE `value`=?", 'ss', (string)$duration, (string)$duration);
        admin_log($admin['id'], 'settings_toast', 'config', 0, "enabled={$enabled}, duration={$duration}");
        $saved = true;
    } elseif ($action === 'promo') {
        $promo_code    = trim(strtoupper(post('promo_code')));
        $promo_pct     = max(0, min(80, (int)post('promo_pct', '0')));
        $promo_label   = trim(post('promo_label'));
        $promo_active  = isset($_POST['promo_active']) ? '1' : '0';
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('promo_code',?)    ON DUPLICATE KEY UPDATE `value`=?", 'ss', $promo_code, $promo_code);
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('promo_pct',?)     ON DUPLICATE KEY UPDATE `value`=?", 'ss', (string)$promo_pct, (string)$promo_pct);
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('promo_label',?)   ON DUPLICATE KEY UPDATE `value`=?", 'ss', $promo_label, $promo_label);
        db_exec("INSERT INTO config (`key`,`value`) VALUES ('promo_active',?)  ON DUPLICATE KEY UPDATE `value`=?", 'ss', $promo_active, $promo_active);
        admin_log($admin['id'], 'promo_updated', 'config', 0, "code={$promo_code}, pct={$promo_pct}%, active={$promo_active}");
        $saved = true;
    }
}

// Current values
$toasts_enabled    = get_config('toasts_enabled', '1');
$toast_duration_ms = (int)get_config('toast_duration_ms', '5000');
$promo_code        = get_config('promo_code', '');
$promo_pct         = (int)get_config('promo_pct', '0');
$promo_label       = get_config('promo_label', '');
$promo_active      = get_config('promo_active', '0');

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
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/admin/settings.php" class="active">⚙️ Settings</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header"><h1>⚙️ Site Settings</h1><p>Toast notifications &amp; promotional offers.</p></div>

    <?php if ($saved): ?>
    <div style="background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#27ae60">✅ Settings saved successfully.</div>
    <?php endif; ?>

    <!-- Toast Notification Controls -->
    <div class="form-wrap mb-24" style="max-width:520px;margin-bottom:28px">
      <div class="form-title" style="font-size:16px">🔔 Toast Notifications</div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="toast">
        <div class="form-group" style="display:flex;align-items:center;gap:12px">
          <input type="checkbox" name="toasts_enabled" id="toastsEnabled" value="1" <?= $toasts_enabled === '1' ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--gold)">
          <label for="toastsEnabled" style="margin:0;font-size:14px;cursor:pointer">Enable toast notifications sitewide</label>
        </div>
        <div class="form-group">
          <label>Display Duration (ms)</label>
          <input type="number" name="toast_duration_ms" value="<?= h((string)$toast_duration_ms) ?>" min="1000" max="30000" step="500">
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px">1000 = 1 second · 5000 = 5 seconds (default) · 30000 = 30 seconds</div>
        </div>
        <div class="form-group">
          <label>Preview</label>
          <button type="button" class="btn-secondary" onclick="previewToast()" style="padding:8px 16px;font-size:13px">Preview Toast</button>
        </div>
        <button type="submit" class="btn-primary btn-full">💾 Save Toast Settings</button>
      </form>
    </div>

    <!-- Promo Codes / Special Offers -->
    <div class="form-wrap" style="max-width:520px">
      <div class="form-title" style="font-size:16px">🎁 Promo Code &amp; Special Offer</div>
      <p class="text-muted" style="font-size:13px;margin-bottom:20px">Set a promo code that applies a percentage discount to all services. When active, a badge appears on the services page.</p>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="promo">
        <div class="form-group">
          <label>Promo Code</label>
          <input type="text" name="promo_code" value="<?= h($promo_code) ?>" maxlength="20" placeholder="e.g. SAVE20" style="text-transform:uppercase">
        </div>
        <div class="form-group">
          <label>Discount Percentage (%)</label>
          <input type="number" name="promo_pct" value="<?= h((string)$promo_pct) ?>" min="0" max="80" step="1" placeholder="0–80">
        </div>
        <div class="form-group">
          <label>Label (shown on site)</label>
          <input type="text" name="promo_label" value="<?= h($promo_label) ?>" maxlength="60" placeholder="e.g. Limited time — 20% off all services!">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:12px">
          <input type="checkbox" name="promo_active" id="promoActive" value="1" <?= $promo_active === '1' ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--gold)">
          <label for="promoActive" style="margin:0;font-size:14px;cursor:pointer">Activate this offer sitewide now</label>
        </div>
        <?php if ($promo_active === '1' && $promo_code): ?>
        <div style="margin-bottom:16px">
          <span class="promo-badge">✅ ACTIVE: <?= h($promo_code) ?> — <?= $promo_pct ?>% off</span>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary btn-full">💾 Save Promo Settings</button>
      </form>
    </div>
  </div>
</div>
</main>
<script>
function previewToast() {
  const dur = parseInt(document.querySelector('[name=toast_duration_ms]').value) || 5000;
  if (typeof Toasts !== 'undefined') {
    const prev = Toasts.duration;
    Toasts.duration = dur;
    Toasts.show('🎲 Preview: GoldRaker just won 500M on Dice!', dur);
    Toasts.duration = prev;
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
