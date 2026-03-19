<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'Forgot Password | GoldOSRS';
$sent = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = post('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = db_one('SELECT id, username FROM users WHERE email=?', 's', $email);
        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            db_exec('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?', 'ssi', $token, $expires, $user['id']);
            $link = SITE_URL . '/reset.php?token=' . $token;
            send_email($email, 'Reset Your Password | GoldOSRS',
                "<h2>⚔️ Password Reset</h2><p>Hi {$user['username']},</p><p>Click the link below to reset your password. It expires in 1 hour.</p><p><a href='{$link}'>{$link}</a></p><p>If you didn't request this, ignore this email.</p>");
        }
        $sent = true; // Always show success to prevent email enumeration
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="section" style="min-height:80vh;display:flex;align-items:center">
  <div class="container">
    <div class="form-wrap" style="max-width:420px">
      <div class="form-title">🔒 Forgot Password</div>
      <?php if ($sent): ?>
        <div class="form-success show">If that email exists in our system, a reset link has been sent.</div>
        <div class="text-center mt-16"><a href="/login.php" class="btn-secondary">Back to Login</a></div>
      <?php else: ?>
        <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="form-group"><label>Email Address</label><input type="email" name="email" required autofocus></div>
          <button type="submit" class="btn-primary btn-full">Send Reset Link</button>
          <p class="text-center mt-16 text-muted" style="font-size:13px"><a href="/login.php" class="text-gold">Back to Login</a></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
