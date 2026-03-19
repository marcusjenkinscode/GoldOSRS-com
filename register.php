<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();

if (is_logged_in()) redirect('/dashboard.php');

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!rate_limit('register', 10)) {
        $error = 'Too many attempts. Please wait.';
    } else {
        $username = post('username');
        $email    = post('email');
        $password = post('password');
        $confirm  = post('confirm');

        if (!$username || !$email || !$password) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($username) < 3 || strlen($username) > 32) {
            $error = 'Username must be 3–32 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username may only contain letters, numbers and underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $exists = db_one('SELECT id FROM users WHERE email=? OR username=? LIMIT 1', 'ss', $email, $username);
            if ($exists) {
                $error = 'Email or username already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ref_code = strtoupper(substr(md5($username . time()), 0, 8));
                $user_id = db_insert(
                    'INSERT INTO users (username, email, password, referral_code, email_verified) VALUES (?,?,?,?,1)',
                    'ssss', $username, $email, $hash, $ref_code
                );
                if ($user_id) {
                    $user = db_one('SELECT * FROM users WHERE id=?', 'i', $user_id);
                    login_user($user);
                    discord_send("🆕 **New User Registered**\n👤 Username: {$username}\n📧 Email: {$email}");
                    redirect('/dashboard.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Register | GoldOSRS';
$page_desc  = 'Create your free GoldOSRS account.';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
  <section class="section" style="min-height:80vh;display:flex;align-items:center">
    <div class="container">
      <div class="form-wrap" style="max-width:420px">
        <div class="form-title">🛡️ Create Account</div>
        <p class="text-muted text-center mb-24">Join the realm's finest marketplace.</p>
        <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="form-group"><label>Username</label><input type="text" name="username" required maxlength="32" autofocus></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
          <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm" required></div>
          <button type="submit" class="btn-primary btn-full">🛡️ Create Account</button>
          <p class="text-center mt-16 text-muted" style="font-size:13px">Already have an account? <a href="/login.php" class="text-gold">Login</a></p>
        </form>
      </div>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
