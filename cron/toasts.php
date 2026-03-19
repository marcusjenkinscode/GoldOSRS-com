<?php
// cron/toasts.php — runs every minute via cPanel cron

if (PHP_SAPI !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '127.0.0.1' && $ip !== '::1') { http_response_code(403); exit; }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();

// If a real order was placed in last 2 mins, already have real toast — skip
$real = db_one("SELECT id FROM orders WHERE created_at > NOW()-INTERVAL 2 MINUTE LIMIT 1");
if ($real) { exit; }

$countries = ['UK','US','AU','CA','DE','NL','SE','NO','FR','BR','NZ','SG'];
$services  = ['OSRS Gold','RS3 Gold','Inferno Cape','Quest Cape','Power Levelling','Boss Service','Account'];
$amounts   = ['50M','100M','200M','500M','1B','2B','5B'];

$country = $countries[array_rand($countries)];
$service = $services[array_rand($services)];
$amount  = in_array($service, ['OSRS Gold','RS3 Gold']) ? $amounts[array_rand($amounts)] : '';

$content = $amount
    ? "🪙 Someone from {$country} just bought {$amount} {$service}"
    : "⚔️ Someone from {$country} just ordered {$service}";

db_insert("INSERT INTO toasts (type, content) VALUES ('simulated', ?)", 's', $content);
