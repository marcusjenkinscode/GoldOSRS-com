<?php
/**
 * GoldOSRS.com – Forgot Password Page (Step 5)
 * Generates a secure token and stores it in password_resets table.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Delete any existing tokens for this email
            $pdo->prepare('DELETE FROM password_resets WHERE email = :email')
                ->execute([':email' => $email]);

            // Generate token and store
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + RESET_TTL);

            $pdo->prepare(
                'INSERT INTO password_resets (email, token, expires_at)
                 VALUES (:email, :token, :expires_at)'
            )->execute([
                ':email'      => $email,
                ':token'      => $token,
                ':expires_at' => $expires_at,
            ]);

            // In production, send email here.
            // For demonstration, we output the link (remove in production!).
            $reset_link = SITE_URL . '/reset-password.php?token=' . urlencode($token);

            // TODO: Send email via mail() or SMTP library
            // mail($email, 'Password Reset', "Click here: $reset_link");
        }

        // Always show success (to prevent email enumeration)
        $success = true;
    }
}

$page_title = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="form-section">
        <h1 class="form-title">Forgot Password?</h1>

        <?php if ($success): ?>
        <div class="flash flash-success">
            If that email is registered, a reset link has been sent. Check your inbox.
        </div>
        <div style="text-align:center; margin-top:1.5rem;">
            <a href="/login.php" class="btn btn-gold">Back to Login</a>
        </div>
        <?php else: ?>
        <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <p style="color:var(--color-grey); font-size:.9rem; margin-bottom:1.5rem;">
            Enter your email address and we'll send you a link to reset your password.
        </p>

        <form method="post" action="/forgot-password.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required autofocus>
            </div>
            <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:1rem;">
                Send Reset Link
            </button>
        </form>
        <p style="text-align:center; margin-top:1.25rem; font-size:.9rem;">
            Remembered it? <a href="/login.php">Log in</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
