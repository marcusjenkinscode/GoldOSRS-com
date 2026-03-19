<?php
/**
 * GoldOSRS.com – Register Page
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

    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $errors[] = 'All fields are required.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3–30 characters (letters, numbers, underscores only).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = get_db();

        // Check uniqueness
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :uname LIMIT 1');
        $stmt->execute([':email' => $email, ':uname' => $username]);
        if ($stmt->fetch()) {
            $errors[] = 'That email or username is already taken.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare(
            'INSERT INTO users (username, email, password) VALUES (:uname, :email, :pw)'
        )->execute([
            ':uname' => $username,
            ':email' => $email,
            ':pw'    => $hash,
        ]);

        $user_id = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        flash_set('success', 'Account created! Welcome to GoldOSRS.');
        header('Location: /');
        exit;
    }
}

$page_title = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="form-section">
        <h1 class="form-title">Create Account</h1>

        <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode('<br>', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" action="/register.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= h($_POST['username'] ?? '') ?>"
                       placeholder="CoolPlayer123" required minlength="3" maxlength="30"
                       pattern="[A-Za-z0-9_]+" autofocus>
                <p class="form-hint">3–30 characters. Letters, numbers, underscores.</p>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Minimum 8 characters" required minlength="8">
            </div>
            <div class="form-group">
                <label for="password2">Confirm Password</label>
                <input type="password" id="password2" name="password2"
                       placeholder="Repeat password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:1rem;">
                Create Account
            </button>
        </form>
        <p style="text-align:center; margin-top:1.25rem; font-size:.9rem;">
            Already have an account? <a href="/login.php">Log in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
