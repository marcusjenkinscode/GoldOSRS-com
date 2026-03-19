<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
$admin = require_admin();
$page_title = 'Orders | Admin | GoldOSRS';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $order_id = (int)post('order_id');
    $action   = post('action');
    if ($order_id && in_array($action, ['paid','processing','completed','cancelled','refunded'], true)) {
        db_exec("UPDATE orders SET status=? WHERE id=?", 'si', $action, $order_id);
        admin_log($admin['id'], "order_status_{$action}", 'order', $order_id);
        if ($action === 'completed') {
            $o = db_one('SELECT * FROM orders WHERE id=?', 'i', $order_id);
            $email = $o['guest_email'] ?? db_one('SELECT email FROM users WHERE id=?', 'i', $o['user_id'])['email'] ?? '';
            if ($email) {
                $ref = 'GOS-'.str_pad($order_id,6,'0',STR_PAD_LEFT);
                send_email($email, "Order Completed — {$ref} | GoldOSRS",
                    "<h2>⚔️ Order Completed!</h2><p>Your order <strong>{$ref}</strong> for <strong>{$o['service_type']}</strong> has been completed. Thank you for using GoldOSRS!</p>");
            }
        }
    }
    header('Location: /admin/orders.php');
    exit;
}

$status_filter = get('status', '');
$search        = get('q', '');
$where = 'WHERE 1=1';
$params = [];
$types  = '';
if ($status_filter) { $where .= ' AND o.status=?'; $types .= 's'; $params[] = $status_filter; }
if ($search)        { $where .= ' AND (o.rsn LIKE ? OR o.guest_email LIKE ? OR u.username LIKE ?)'; $types .= 'sss'; $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); }

$orders = db_all("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.id=o.user_id {$where} ORDER BY o.created_at DESC LIMIT 100", $types, ...$params);

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <h2>🛡️ Admin Panel</h2>
    <nav class="admin-nav">
      <a href="/admin/">📊 Dashboard</a>
      <a href="/admin/chat.php">💬 Live Chat</a>
      <a href="/admin/orders.php" class="active">📋 Orders</a>
      <a href="/admin/users.php">👥 Users</a>
      <a href="/admin/gambling.php">🎲 Gambling</a>
      <a href="/admin/prices.php">💰 Prices</a>
      <a href="/">🌐 View Site</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="dash-header"><h1>Orders</h1></div>

    <!-- Filters -->
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <form method="GET" style="display:flex;gap:8px;flex:1;min-width:260px">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search RSN, email, username…" style="flex:1;padding:9px 12px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:13px;outline:none">
        <button type="submit" class="btn-gold" style="padding:9px 16px">Search</button>
      </form>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php foreach (['','pending','paid','processing','completed','cancelled'] as $s): ?>
        <a href="/admin/orders.php?status=<?= $s ?>&q=<?= h($search) ?>" class="tab-btn <?= $status_filter===$s?'active':'' ?>" style="padding:8px 14px;font-size:12px"><?= $s?ucfirst($s):'All' ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>User</th><th>Service</th><th>RSN</th><th>Amount</th><th>Price</th><th>Pay</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): $ref='GOS-'.str_pad($o['id'],6,'0',STR_PAD_LEFT); ?>
        <tr>
          <td class="text-gold" style="font-size:12px"><?= h($ref) ?></td>
          <td style="font-size:12px"><?= h($o['username']??$o['guest_rsn']??'Guest') ?></td>
          <td style="font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis"><?= h($o['service_type']??$o['type']) ?></td>
          <td style="font-size:12px"><?= h($o['rsn']??'—') ?></td>
          <td style="font-size:12px"><?= $o['amount']?fmt_gp((int)$o['amount']):'—' ?></td>
          <td style="font-size:12px"><?= $o['price_usd']?'$'.number_format((float)$o['price_usd'],2):'—' ?></td>
          <td style="font-size:12px"><?= h($o['payment_method']??'—') ?></td>
          <td><span class="status-badge status-<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
          <td style="font-size:11px;color:var(--text-muted)"><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
          <td>
            <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <?php if ($o['status']==='pending'): ?><button name="action" value="paid" class="btn-gold" style="padding:4px 8px;font-size:11px">Paid</button><?php endif; ?>
              <?php if (in_array($o['status'],['pending','paid'])): ?><button name="action" value="processing" class="btn-secondary" style="padding:4px 8px;font-size:11px">Processing</button><?php endif; ?>
              <?php if (in_array($o['status'],['paid','processing'])): ?><button name="action" value="completed" class="btn-secondary" style="padding:4px 8px;font-size:11px">✅ Complete</button><?php endif; ?>
              <?php if (!in_array($o['status'],['completed','cancelled'])): ?><button name="action" value="cancelled" style="padding:4px 8px;font-size:11px;background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);color:#e74c3c;border-radius:4px;cursor:pointer;font-family:'Cinzel',serif">Cancel</button><?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="10" class="text-center text-muted" style="padding:32px">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
