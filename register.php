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
        $username  = post('username');
        $email     = post('email');
        $password  = post('password');
        $confirm   = post('confirm');
        $ref_input = strtoupper(trim(post('referral_code')));

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
                // Validate referral code
                $referrer_id = null;
                if ($ref_input) {
                    $referrer = db_one('SELECT id FROM users WHERE referral_code=? LIMIT 1', 's', $ref_input);
                    if ($referrer) {
                        $referrer_id = (int)$referrer['id'];
                    } else {
                        $error = 'Invalid referral code. Leave blank if you don\'t have one.';
                    }
                }

                if (!$error) {
                    $hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $ref_code = strtoupper(substr(md5($username . time()), 0, 8));
                    $user_id  = db_insert(
                        'INSERT INTO users (username, email, password, referral_code, referred_by, email_verified) VALUES (?,?,?,?,?,1)',
                        'ssssi', $username, $email, $hash, $ref_code, $referrer_id
                    );
                    if ($user_id) {
                        $user = db_one('SELECT * FROM users WHERE id=?', 'i', $user_id);
                        login_user($user);
                        discord_send("🆕 **New User Registered**\n👤 Username: {$username}\n📧 Email: {$email}" . ($referrer_id ? "\n🔗 Referred by user #{$referrer_id}" : ''));
                        redirect('/dashboard.php');
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
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
          <div class="form-group"><label>Username</label><input type="text" name="username" required maxlength="32" autofocus value="<?= h(post('username')) ?>"></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= h(post('email')) ?>"></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
          <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm" required></div>
          <div class="form-group">
            <label>Referral Code <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
            <input type="text" name="referral_code" maxlength="20" placeholder="Enter invite code if you have one" value="<?= h(post('referral_code')) ?>" style="text-transform:uppercase">
          </div>
          <button type="submit" class="btn-primary btn-full">🛡️ Create Account</button>
        </form>

        <div style="text-align:center;margin:20px 0;color:var(--text-muted);font-size:12px;letter-spacing:1px">— OR SIGN UP WITH —</div>
        <div style="display:flex;gap:10px;justify-content:center;margin-bottom:16px">
          <a href="/auth/google.php" class="btn-secondary" style="display:flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M16.51 7.74H9v2.7h4.3c-.41 1.9-2.06 3.08-4.3 3.08A4.77 4.77 0 0 1 9 4.06c1.2 0 2.3.43 3.15 1.14l1.96-1.96A7.41 7.41 0 0 0 9 1.5 7.5 7.5 0 0 0 9 16.5c4.08 0 7.38-2.97 7.5-7h-0z" fill="#4285F4"/><path d="M2.29 5.52 4.66 7.26A4.77 4.77 0 0 1 9 4.06c1.2 0 2.3.43 3.15 1.14l1.96-1.96A7.41 7.41 0 0 0 9 1.5a7.5 7.5 0 0 0-6.71 4.02z" fill="#EA4335"/><path d="M9 16.5c1.93 0 3.68-.67 5.04-1.77l-2.33-1.97A4.77 4.77 0 0 1 4.55 10.4L2.22 12.2A7.5 7.5 0 0 0 9 16.5z" fill="#34A853"/><path d="M16.5 9c0-.5-.04-.98-.13-1.44H9v2.7h4.3a3.77 3.77 0 0 1-1.63 2.07l2.33 1.97A7.46 7.46 0 0 0 16.5 9z" fill="#FBBC05"/></svg>
            Google
          </a>
          <a href="/auth/discord.php" class="btn-secondary" style="display:flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px">
            <svg width="18" height="14" viewBox="0 0 71 55" fill="#5865F2"><path d="M60.1 4.6A58.5 58.5 0 0 0 45.6.7a40.7 40.7 0 0 0-1.8 3.7 54.2 54.2 0 0 0-16.4 0A40 40 0 0 0 25.6.7 58.2 58.2 0 0 0 11 4.6C1.6 18.9-.9 32.8.3 46.5a58.7 58.7 0 0 0 17.9 9.1 43.7 43.7 0 0 0 3.8-6.2 38.4 38.4 0 0 1-6-2.9l1.4-1.1a41.9 41.9 0 0 0 35.9 0l1.5 1.1a38.2 38.2 0 0 1-6 2.9 43.5 43.5 0 0 0 3.8 6.2 58.5 58.5 0 0 0 17.9-9.1c1.4-15.7-2.4-29.5-10.4-43.9zM23.7 38.1c-3.5 0-6.4-3.2-6.4-7.2s2.8-7.2 6.4-7.2 6.5 3.2 6.4 7.2c0 4-2.8 7.2-6.4 7.2zm23.5 0c-3.5 0-6.4-3.2-6.4-7.2s2.8-7.2 6.4-7.2 6.4 3.2 6.4 7.2-2.8 7.2-6.4 7.2z"/></svg>
            Discord
          </a>
        </div>
        <p class="text-center mt-16 text-muted" style="font-size:13px">Already have an account? <a href="/login.php" class="text-gold">Login</a></p>
      </div>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
