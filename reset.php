<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'Reset Password | GoldOSRS';
$token = get('token');
$error = ''; $done = false;

$user = $token ? db_one('SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW()', 's', $token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $token    = post('token');
    $password = post('password');
    $confirm  = post('confirm');
    $user     = $token ? db_one('SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW()', 's', $token) : null;
    if (!$user)                { $error = 'Invalid or expired reset link.'; }
    elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
    elseif ($password !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
        db_exec('UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?', 'si', $hash, $user['id']);
        $done = true;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="section" style="min-height:80vh;display:flex;align-items:center">
  <div class="container">
    <div class="form-wrap" style="max-width:420px">
      <div class="form-title">🔒 Reset Password</div>
      <?php if ($done): ?>
        <div class="form-success show">Password updated! You can now login.</div>
        <div class="text-center mt-16"><a href="/login.php" class="btn-primary">Login Now</a></div>
      <?php elseif (!$user && !$_POST): ?>
        <div class="form-alert show">Invalid or expired reset link. <a href="/forgot.php" class="text-gold">Request a new one.</a></div>
      <?php else: ?>
        <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= h($token) ?>">
          <div class="form-group"><label>New Password</label><input type="password" name="password" minlength="6" required autofocus></div>
          <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm" required></div>
          <button type="submit" class="btn-primary btn-full">Set New Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
