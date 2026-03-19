<?php
/**
 * GoldOSRS.com – Reset Password Page (Step 5)
 * Validates the token and allows the user to set a new password.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

$token   = trim($_GET['token'] ?? '');
$errors  = [];
$success = false;
$valid   = false;
$email   = '';

// Validate token on every request (GET and POST)
if ($token !== '') {
    // Constant-time safe lookup
    $pdo  = get_db();
    $stmt = $pdo->prepare(
        'SELECT email, expires_at FROM password_resets WHERE token = :token LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $reset = $stmt->fetch();

    if ($reset && strtotime($reset['expires_at']) > time()) {
        $valid = true;
        $email = $reset['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$valid) {
        $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $pdo->prepare('UPDATE users SET password = :pw WHERE email = :email')
                ->execute([':pw' => $hash, ':email' => $email]);

            // Delete used token (and all tokens for this email)
            $pdo->prepare('DELETE FROM password_resets WHERE email = :email')
                ->execute([':email' => $email]);

            $success = true;
        }
    }
}

$page_title = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="form-section">
        <h1 class="form-title">Reset Password</h1>

        <?php if ($success): ?>
        <div class="flash flash-success">Your password has been reset successfully!</div>
        <div style="text-align:center; margin-top:1.5rem;">
            <a href="/login.php" class="btn btn-gold">Log In Now</a>
        </div>

        <?php elseif (!$valid && $token !== ''): ?>
        <div class="flash flash-error">
            This reset link is <strong>invalid or has expired</strong>
            (links are valid for <?= RESET_TTL / 60 ?> minutes).
        </div>
        <div style="text-align:center; margin-top:1.5rem;">
            <a href="/forgot-password.php" class="btn btn-gold">Request New Link</a>
        </div>

        <?php elseif ($token === ''): ?>
        <div class="flash flash-error">No reset token provided.</div>
        <div style="text-align:center; margin-top:1.5rem;">
            <a href="/forgot-password.php" class="btn btn-gold">Request Reset Link</a>
        </div>

        <?php else: ?>
        <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" action="/reset-password.php?token=<?= urlencode($token) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Minimum 8 characters" required autofocus minlength="8">
            </div>
            <div class="form-group">
                <label for="password2">Confirm Password</label>
                <input type="password" id="password2" name="password2"
                       placeholder="Repeat your new password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:1rem;">
                Set New Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
