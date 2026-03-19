<?php
/**
 * Shared helper functions for GoldOSRS.com
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// Session helpers
// ---------------------------------------------------------------------------

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure',   '1');
        ini_set('session.use_strict_mode', '1');
        session_name(SESSION_NAME);
        session_start();
    }
}

function is_logged_in(): bool {
    start_session();
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

// ---------------------------------------------------------------------------
// CSRF helpers
// ---------------------------------------------------------------------------

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void {
    start_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ---------------------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------------------

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------------
// Basket helpers (stored in session)
// ---------------------------------------------------------------------------

function basket_add(string $service, float $amount, string $payment_method): void {
    start_session();
    if (!isset($_SESSION['basket'])) {
        $_SESSION['basket'] = [];
    }
    $_SESSION['basket'][] = [
        'service'        => $service,
        'amount'         => $amount,
        'payment_method' => $payment_method,
    ];
}

function basket_clear(): void {
    start_session();
    $_SESSION['basket'] = [];
}

function basket_count(): int {
    start_session();
    return count($_SESSION['basket'] ?? []);
}

function basket_total(): float {
    start_session();
    return array_sum(array_column($_SESSION['basket'] ?? [], 'amount'));
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------

function flash_set(string $type, string $message): void {
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    start_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Misc
// ---------------------------------------------------------------------------

function format_gp(float $amount): string {
    if ($amount >= 1_000_000_000) {
        return number_format($amount / 1_000_000_000, 1) . 'B gp';
    }
    if ($amount >= 1_000_000) {
        return number_format($amount / 1_000_000, 1) . 'M gp';
    }
    if ($amount >= 1_000) {
        return number_format($amount / 1_000, 1) . 'K gp';
    }
    return number_format($amount, 0) . ' gp';
}
