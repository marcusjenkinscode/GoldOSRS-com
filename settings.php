<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$user = require_login();
$page_title = 'Settings | GoldOSRS';
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'change_password') {
        $current = post('current_password');
        $new     = post('new_password');
        $confirm = post('confirm_password');
        if (!password_verify($current, $user['password'])) { $error = 'Current password is incorrect.'; }
        elseif (strlen($new) < 6)                           { $error = 'New password must be at least 6 characters.'; }
        elseif ($new !== $confirm)                          { $error = 'Passwords do not match.'; }
        else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
            db_exec('UPDATE users SET password=? WHERE id=?', 'si', $hash, $user['id']);
            $success = 'Password updated successfully.';
        }
    } elseif ($action === 'change_email') {
        $email = post('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email.'; }
        else {
            $exists = db_one('SELECT id FROM users WHERE email=? AND id!=?', 'si', $email, $user['id']);
            if ($exists) { $error = 'Email already in use.'; }
            else { db_exec('UPDATE users SET email=? WHERE id=?', 'si', $email, $user['id']); $success = 'Email updated.'; }
        }
    }
    $user = db_one('SELECT * FROM users WHERE id=?', 'i', $user['id']);
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<div class="dash-layout">
  <aside class="dash-sidebar">
    <div style="padding:20px;border-bottom:1px solid var(--border);margin-bottom:12px"><div style="font-size:13px;color:var(--text-muted)">Logged in as</div><div style="color:var(--gold);font-weight:700;font-size:16px"><?= h($user['username']) ?></div></div>
    <nav class="dash-nav">
      <a href="/dashboard.php">📊 Overview</a>
      <a href="/history.php">📋 Orders</a>
      <a href="/deposit.php">💰 Deposit</a>
      <a href="/withdraw.php">📤 Withdraw</a>
      <a href="/gambling.php">🎲 Gambling</a>
      <a href="/settings.php" class="active">⚙️ Settings</a>
      <a href="/logout.php" style="color:var(--red)">🚪 Logout</a>
    </nav>
  </aside>
  <div class="dash-main">
    <div class="dash-header"><h1>⚙️ Settings</h1></div>
    <?php if ($success): ?><div class="form-success show"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>

    <div class="form-wrap mb-24" style="max-width:480px;margin-bottom:24px">
      <div class="form-title" style="font-size:16px">📧 Change Email</div>
      <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="action" value="change_email">
        <div class="form-group"><label>New Email</label><input type="email" name="email" value="<?= h($user['email']) ?>" required></div>
        <button type="submit" class="btn-primary btn-full">Update Email</button>
      </form>
    </div>

    <div class="form-wrap" style="max-width:480px">
      <div class="form-title" style="font-size:16px">🔒 Change Password</div>
      <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="action" value="change_password">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="6" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
        <button type="submit" class="btn-primary btn-full">Update Password</button>
      </form>
    </div>

    <div class="card mt-24" style="max-width:480px">
      <h3>Account Info</h3>
      <p class="text-muted mt-8" style="font-size:13px">Username: <strong class="text-gold"><?= h($user['username']) ?></strong></p>
      <p class="text-muted" style="font-size:13px">Member since: <strong><?= date('d F Y', strtotime($user['created_at'])) ?></strong></p>
      <p class="text-muted" style="font-size:13px">Referral code: <strong class="text-gold"><?= h($user['referral_code'] ?? 'N/A') ?></strong></p>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
