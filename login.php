<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();

if (is_logged_in()) redirect('/dashboard.php');

$error = '';
$redir = get('redir', '/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!rate_limit('login', 3)) {
        $error = 'Too many attempts. Please wait.';
    } else {
        $ident    = post('ident');
        $password = post('password');

        if (!$ident || !$password) {
            $error = 'Please fill in all fields.';
        } else {
            $user = db_one('SELECT * FROM users WHERE email=? OR username=? LIMIT 1', 'ss', $ident, $ident);
            if ($user && password_verify($password, $user['password'])) {
                login_user($user);
                log_info('Login: user #' . $user['id'] . ' from ' . get_ip());
                redirect($redir);
            } else {
                $error = 'Invalid username/email or password.';
            }
        }
    }
}

$page_title = 'Login | GoldOSRS';
$page_desc  = 'Login to your GoldOSRS account.';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
  <section class="section" style="min-height:80vh;display:flex;align-items:center">
    <div class="container">
      <div class="form-wrap" style="max-width:420px">
        <div class="form-title">⚔️ Login</div>
        <p class="text-muted text-center mb-24">Welcome back, adventurer.</p>
        <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="redir" value="<?= h($redir) ?>">
          <div class="form-group"><label>Username or Email</label><input type="text" name="ident" required autocomplete="username" autofocus></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
          <button type="submit" class="btn-primary btn-full">⚔️ Login</button>
          <p class="text-center mt-16 text-muted" style="font-size:13px">Don't have an account? <a href="/register.php" class="text-gold">Register</a></p>
          <p class="text-center mt-8 text-muted" style="font-size:13px"><a href="/forgot.php" class="text-gold">Forgot password?</a></p>
        </form>
      </div>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
