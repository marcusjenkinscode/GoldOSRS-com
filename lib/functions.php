<?php
// lib/functions.php — Core helpers

// ── Bootstrap ─────────────────────────────────────────────────────────────────
function bootstrap(): void {
    if (!defined('ROOT_PATH')) require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/electrum.php';

    if (!is_dir(LOG_PATH) && !@mkdir(LOG_PATH, 0755, true)) {
        error_log('GoldOSRS: could not create LOG_PATH ' . LOG_PATH);
    }
    if (!is_dir(DATA_PATH) && !@mkdir(DATA_PATH, 0755, true)) {
        error_log('GoldOSRS: could not create DATA_PATH ' . DATA_PATH);
    }

    session_name(SESSION_NAME);
    if (session_status() === PHP_SESSION_NONE) {
        // PHP-FPM on IONOS: suppress the "Cannot send session cookie — headers
        // already sent" / cookie_secure warning that fires when the SSL is
        // terminated upstream by the load balancer (the Apache process sees HTTP
        // internally). Suppressing here prevents a spurious E_WARNING that can
        // propagate as a 500 on some FastCGI configurations.
        if (!@session_start()) {
            error_log('GoldOSRS: session_start() failed');
        }
    }

    // Regenerate session ID on first visit (prevent session fixation)
    if (empty($_SESSION['_init'])) {
        if (!@session_regenerate_id(true)) {
            // Non-fatal: log but continue — the session is still usable
            error_log('GoldOSRS: session_regenerate_id() failed');
        }
        $_SESSION['_init'] = time();
    }
}

// ── Security ──────────────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid request token.']));
    }
}

function rate_limit(string $action, int $seconds = 0): bool {
    $key = 'rl_' . $action;
    $limit = $seconds ?: RATE_LIMIT_SECONDS;
    $last = $_SESSION[$key] ?? 0;
    if (time() - $last < $limit) return false;
    $_SESSION[$key] = time();
    return true;
}

function require_login(): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php?redir=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    return db_one('SELECT * FROM users WHERE id=?', 'i', $_SESSION['user_id']) ?? [];
}

function require_admin(): array {
    $user = require_login();
    if (($user['role'] ?? '') !== 'admin') {
        header('Location: /');
        exit;
    }
    return $user;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $cached = null;
    if ($cached) return $cached;
    $cached = db_one('SELECT * FROM users WHERE id=?', 'i', $_SESSION['user_id']);
    return $cached;
}

// ── Auth ──────────────────────────────────────────────────────────────────────
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    // Update last login
    $today = date('Y-m-d');
    if (($user['last_login'] ?? '') !== $today) {
        db_exec('UPDATE users SET last_login=?, login_streak=login_streak+1 WHERE id=?', 'si', $today, $user['id']);
    }
}

function logout_user(): void {
    session_unset();
    session_destroy();
}

// ── Input sanitization ────────────────────────────────────────────────────────
function post(string $key, string $default = ''): string {
    return trim((string)($_POST[$key] ?? $default));
}

function get(string $key, string $default = ''): string {
    return trim((string)($_GET[$key] ?? $default));
}

function post_int(string $key, int $default = 0): int {
    return (int)($_POST[$key] ?? $default);
}

// ── Prices ────────────────────────────────────────────────────────────────────
function get_prices(): array {
    static $prices = null;
    if ($prices !== null) return $prices;
    $rows = db_all('SELECT `key`, `value` FROM prices');
    $prices = [];
    foreach ($rows as $r) $prices[$r['key']] = (float)$r['value'];
    return $prices;
}

function get_price(string $key, float $default = 0): float {
    return get_prices()[$key] ?? $default;
}

// ── Config ────────────────────────────────────────────────────────────────────
function get_config(string $key, string $default = ''): string {
    static $cfg = null;
    if ($cfg === null) {
        $rows = db_all('SELECT `key`, `value` FROM config');
        $cfg = [];
        foreach ($rows as $r) $cfg[$r['key']] = $r['value'];
    }
    return $cfg[$key] ?? $default;
}

// ── Orders ────────────────────────────────────────────────────────────────────
function generate_order_ref(): string {
    return 'GOS-' . strtoupper(substr(uniqid(), -6));
}

// ── GP formatting ─────────────────────────────────────────────────────────────
function fmt_gp(int $millions): string {
    if ($millions >= 1000) return number_format($millions / 1000, 1) . 'B GP';
    return $millions . 'M GP';
}

// ── Discord webhook ───────────────────────────────────────────────────────────
function discord_send(string $message, string $username = 'GoldOSRS Bot'): bool {
    if (!defined('DISCORD_WEBHOOK_URL') || !DISCORD_WEBHOOK_URL || strpos(DISCORD_WEBHOOK_URL, 'YOUR_WEBHOOK') !== false) {
        log_error('Discord webhook not configured');
        return false;
    }
    $payload = json_encode([
        'content'  => mb_substr($message, 0, 2000),
        'username' => $username,
    ]);
    $cmd = sprintf(
        'curl -s -o /dev/null -X POST -H "Content-Type: application/json" -d %s %s 2>&1',
        escapeshellarg($payload),
        escapeshellarg(DISCORD_WEBHOOK_URL)
    );
    exec($cmd . ' &');  // Non-blocking
    return true;
}

// Notify admin of new chat session via Discord
function discord_notify_chat(int $session_id, string $user_name, string $first_msg): void {
    $url = SITE_URL . '/admin/chat.php?session=' . $session_id;
    discord_send("🆕 **New Support Chat** | Session #$session_id\n👤 User: **$user_name**\n💬 Message: " . mb_substr($first_msg, 0, 200) . "\n🔗 Reply: $url", 'GoldOSRS Support');
}

// ── Email (basic PHP mail) ────────────────────────────────────────────────────
function send_email(string $to, string $subject, string $body_html): bool {
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . SITE_EMAIL,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    return mail($to, $subject, $body_html, $headers);
}

function email_order_received(string $to, string $rsn, string $service, string $ref): void {
    $body = "<h2>⚔️ Order Received — {$ref}</h2>
    <p>Hi {$rsn},</p>
    <p>We've received your order for: <strong>{$service}</strong>.</p>
    <p>Our team will contact you shortly via the live chat on site or Discord.</p>
    <p>Order reference: <strong>{$ref}</strong></p>
    <br><p>— GoldOSRS Team</p>";
    send_email($to, "Order Received — $ref | GoldOSRS", $body);
}

function email_payment_confirmed(string $to, string $rsn, string $ref): void {
    $body = "<h2>✅ Payment Confirmed — {$ref}</h2>
    <p>Hi {$rsn},</p>
    <p>Your payment has been confirmed. Your order is now being processed.</p>
    <p>Order reference: <strong>{$ref}</strong></p>
    <br><p>— GoldOSRS Team</p>";
    send_email($to, "Payment Confirmed — $ref | GoldOSRS", $body);
}

// ── Logging ───────────────────────────────────────────────────────────────────
function log_error(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $msg . "\n";
    if (!@file_put_contents(LOG_PATH . '/error.log', $line, FILE_APPEND | LOCK_EX)) {
        // Fallback to the system error log if the file can't be written
        error_log('GoldOSRS ERROR: ' . $msg);
    }
}

function log_info(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] INFO: ' . $msg . "\n";
    if (!@file_put_contents(LOG_PATH . '/app.log', $line, FILE_APPEND | LOCK_EX)) {
        error_log('GoldOSRS INFO: ' . $msg);
    }
}

function admin_log(int $admin_id, string $action, string $target_type = '', int $target_id = 0, string $details = ''): void {
    db_insert('INSERT INTO admin_log (admin_id, action, target_type, target_id, details, ip) VALUES (?,?,?,?,?,?)',
        'isisss', $admin_id, $action, $target_type, $target_id, $details, $_SERVER['REMOTE_ADDR'] ?? '');
}

// ── Provably fair helpers ─────────────────────────────────────────────────────
function generate_server_seed(): string {
    return bin2hex(random_bytes(32));
}

function hash_seed(string $seed): string {
    return hash('sha256', $seed);
}

function provably_fair_roll(string $server_seed, string $client_seed, int $nonce, int $max = 10000): int {
    $combined = $server_seed . ':' . $client_seed . ':' . $nonce;
    $hmac = hash_hmac('sha256', $combined, 'goldosrs_secret_key');
    // Take first 8 hex chars as an integer
    $num = hexdec(substr($hmac, 0, 8));
    return (int)($num % $max);
}

// ── CSRF field HTML helper ────────────────────────────────────────────────────
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

// ── IP helpers ────────────────────────────────────────────────────────────────
function get_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            return trim(explode(',', $_SERVER[$k])[0]);
        }
    }
    return '0.0.0.0';
}

// ── JSON response helper (for API endpoints) ──────────────────────────────────
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

// ── Redirect ──────────────────────────────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
