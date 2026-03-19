<?php
// cron/check_btc.php
// cPanel cron: * * * * * php /home/youruser/public_html/cron/check_btc.php >> /dev/null 2>&1

if (PHP_SAPI !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '127.0.0.1' && $ip !== '::1') { http_response_code(403); exit; }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();

// Lock file to prevent overlapping runs
$lock = DATA_PATH . '/btc_check.lock';
if (file_exists($lock) && (time() - filemtime($lock)) < 55) { exit; }
file_put_contents($lock, time());

log_info('BTC cron: checking pending deposits and orders');

// ── Check pending DEPOSITS ────────────────────────────────────────────────────
$pending_deposits = db_all(
    "SELECT * FROM deposits WHERE status='pending' AND created_at > NOW()-INTERVAL 2 HOUR"
);

foreach ($pending_deposits as $dep) {
    try {
        $info = blockchain_check_address($dep['address']);
        $received_sat = $info['total_received'];
        $received_btc = satoshis_to_btc($received_sat);

        if ($received_btc <= 0) continue;

        $required_btc = (float)$dep['amount_crypto'];
        // Allow 5% tolerance for network fees
        if ($required_btc > 0 && $received_btc < ($required_btc * 0.95)) continue;

        // Check confirmations on latest tx
        $confirmations = 0;
        if (!empty($info['txs'][0])) {
            $confirmations = (int)($info['txs'][0]['confirmations'] ?? 0);
        }

        if ($confirmations < BTC_CONFIRMATIONS_REQUIRED) {
            log_info("Deposit #{$dep['id']}: {$confirmations} confirmations, waiting for " . BTC_CONFIRMATIONS_REQUIRED);
            continue;
        }

        $txid     = $info['txs'][0]['hash'] ?? '';
        $btc_price = get_btc_price_usd();
        $usd_value = $received_btc * $btc_price;
        $gp_credit = (int)round($usd_value * GP_PER_USD);

        // Credit GP to user
        db_exec('UPDATE deposits SET status="credited", amount_crypto=?, amount_usd=?, gp_credited=?, txid=?, confirmations=?, confirmed_at=NOW() WHERE id=?',
            'ddiisi', $received_btc, $usd_value, $gp_credit, $txid, $confirmations, $dep['id']);
        db_exec('UPDATE users SET balance_osrs=balance_osrs+? WHERE id=?', 'ii', $gp_credit, $dep['user_id']);

        // Real toast
        $user = db_one('SELECT username FROM users WHERE id=?', 'i', $dep['user_id']);
        db_insert("INSERT INTO toasts (type, content) VALUES ('real', ?)", 's',
            "₿ " . ($user['username'] ?? 'Someone') . " just deposited " . fmt_gp($gp_credit));

        // Email user
        $u = db_one('SELECT * FROM users WHERE id=?', 'i', $dep['user_id']);
        if ($u) {
            email_payment_confirmed($u['email'], $u['username'], "DEP-{$dep['id']}");
        }
        discord_send("✅ **Deposit Confirmed** | User #{$dep['user_id']}\n₿ {$received_btc} BTC (≈\${$usd_value})\n🪙 Credited: " . fmt_gp($gp_credit) . "\nTXID: `{$txid}`");
        log_info("Deposit #{$dep['id']} confirmed: {$received_btc} BTC → " . fmt_gp($gp_credit));

    } catch (Throwable $e) {
        log_error("Deposit check #{$dep['id']} error: " . $e->getMessage());
    }
}

// ── Check pending ORDERS (crypto payment) ────────────────────────────────────
$pending_orders = db_all(
    "SELECT * FROM orders WHERE status='pending' AND payment_method='crypto' AND btc_address IS NOT NULL AND created_at > NOW()-INTERVAL 2 HOUR"
);

foreach ($pending_orders as $order) {
    try {
        $info = blockchain_check_address($order['btc_address']);
        $received_btc = satoshis_to_btc($info['total_received']);
        if ($received_btc <= 0) continue;

        $required_btc = (float)$order['btc_amount'];
        if ($required_btc > 0 && $received_btc < ($required_btc * 0.95)) continue;

        $confirmations = (int)($info['txs'][0]['confirmations'] ?? 0);
        if ($confirmations < BTC_CONFIRMATIONS_REQUIRED) continue;

        $txid = $info['txs'][0]['hash'] ?? '';
        db_exec("UPDATE orders SET status='paid', btc_txid=?, paid_at=NOW() WHERE id=?", 'si', $txid, $order['id']);

        // Real toast
        $label = $order['service_type'] ?? $order['type'];
        db_insert("INSERT INTO toasts (type, content) VALUES ('real', ?)", 's',
            "🪙 New order paid: {$label} — " . ($order['rsn'] ?? 'Customer'));

        // Email
        $email = $order['guest_email'] ?? db_one('SELECT email FROM users WHERE id=?', 'i', $order['user_id'])['email'] ?? '';
        $ref   = 'GOS-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        if ($email) email_payment_confirmed($email, $order['rsn'] ?? 'Customer', $ref);

        discord_send("💰 **Order PAID #{$ref}**\n⚔️ Service: {$label}\n👤 RSN: {$order['rsn']}\n₿ TXID: `{$txid}`");
        log_info("Order #{$order['id']} marked paid");

    } catch (Throwable $e) {
        log_error("Order check #{$order['id']} error: " . $e->getMessage());
    }
}

// Clean up lock
unlink($lock);
log_info('BTC cron: complete');
