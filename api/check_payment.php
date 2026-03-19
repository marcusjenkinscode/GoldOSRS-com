<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');

$action = get('action');

// Generate deposit address for logged-in user
if ($action === 'gen_address' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) { json_out(['error' => 'Login required'], 401); }
    csrf_check();
    $currency = post('currency', 'BTC');
    $user = current_user();

    $address = ($currency === 'BTC') ? get_new_btc_address()
             : (($currency === 'ETH') ? STATIC_ETH_ADDRESS : STATIC_LTC_ADDRESS);

    db_insert('INSERT INTO deposits (user_id, currency, address, status) VALUES (?,?,?,?)',
        'isss', $user['id'], $currency, $address, 'pending');

    json_out(['address' => $address, 'currency' => $currency]);
}

// Called by cron — also accessible internally
if ($action === 'run_check') {
    // Security: only from localhost or CLI
    $ip = get_ip();
    if (PHP_SAPI !== 'cli' && $ip !== '127.0.0.1' && $ip !== '::1') {
        json_out(['error' => 'Forbidden'], 403);
    }
    // This logic lives in cron/check_btc.php — redirect there
    require_once __DIR__ . '/../cron/check_btc.php';
    exit;
}

json_out(['error' => 'Unknown action'], 400);
