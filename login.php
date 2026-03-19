<?php
/**
 * GoldOSRS.com – Login Page
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Please fill in all fields.';
    }

    if (empty($errors)) {
        $pdo  = get_db();
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID on successful login (security best practice)
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            flash_set('success', 'Welcome back!');
            $redirect = $_GET['redirect'] ?? '/';
            // Validate redirect
            if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
                $redirect = '/';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="form-section">
        <h1 class="form-title">Login</h1>

        <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" action="/login.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password" required>
            </div>
            <div style="text-align:right; margin-bottom:.75rem;">
                <a href="/forgot-password.php" style="font-size:.85rem;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-gold btn-block btn-lg">
                Log In
            </button>
        </form>
        <p style="text-align:center; margin-top:1.25rem; font-size:.9rem;">
            Don't have an account? <a href="/register.php">Register</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
