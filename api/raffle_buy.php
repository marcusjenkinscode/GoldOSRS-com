<?php
// api/raffle_buy.php — purchase raffle tickets with OSRS GP balance
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');

if (!is_logged_in()) { json_out(['error' => 'Login required'], 401); }
csrf_check();
if (!rate_limit('raffle_buy', 5)) { json_out(['error' => 'Too many requests'], 429); }

$user    = current_user();
$qty     = max(1, min(100, (int)post('qty')));
$cost_m  = max(1, (int)post('cost_m')); // cost in millions of GP

$valid = [
    1  => 5,
    5  => 20,
    15 => 50,
];
if (!isset($valid[$qty]) || $valid[$qty] !== $cost_m) {
    json_out(['error' => 'Invalid ticket package'], 400);
}
if ($user['balance_osrs'] < $cost_m) {
    json_out(['error' => 'Insufficient OSRS balance. Deposit GP first.'], 400);
}

// Deduct balance
db_exec('UPDATE users SET balance_osrs = balance_osrs - ? WHERE id = ?', 'ii', $cost_m, $user['id']);

// Record raffle entry (upsert into raffle_entries)
$existing = db_one('SELECT id, tickets FROM raffle_entries WHERE user_id = ? LIMIT 1', 'i', $user['id']);
if ($existing) {
    db_exec('UPDATE raffle_entries SET tickets = tickets + ? WHERE id = ?', 'ii', $qty, $existing['id']);
} else {
    db_insert('INSERT INTO raffle_entries (user_id, tickets, draw_date) VALUES (?, ?, DATE(NOW()))', 'ii', $user['id'], $qty);
}

$updated = db_one('SELECT balance_osrs FROM users WHERE id = ?', 'i', $user['id']);
json_out([
    'success'     => true,
    'message'     => "✅ {$qty} ticket" . ($qty > 1 ? 's' : '') . " purchased! Good luck! 🎟️",
    'new_balance' => $updated['balance_osrs'],
    'tickets_bought' => $qty,
]);
