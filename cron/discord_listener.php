<?php
// cron/discord_listener.php
// cPanel cron: * * * * * php /home/youruser/public_html/cron/discord_listener.php >> /dev/null 2>&1

if (PHP_SAPI !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '127.0.0.1' && $ip !== '::1') { http_response_code(403); exit; }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();

if (!defined('DISCORD_BOT_TOKEN') || str_contains(DISCORD_BOT_TOKEN, 'YOUR_BOT')) {
    exit; // Not configured
}

$lock = DATA_PATH . '/discord_listener.lock';
if (file_exists($lock) && (time() - filemtime($lock)) < 55) { exit; }
file_put_contents($lock, time());

// Load last processed message ID
$cache = [];
if (file_exists(DISCORD_CACHE_FILE)) {
    $cache = json_decode(file_get_contents(DISCORD_CACHE_FILE), true) ?? [];
}
$last_message_id = $cache['last_message_id'] ?? '0';

// Fetch recent messages from Discord support channel
$ch = curl_init("https://discord.com/api/v10/channels/" . DISCORD_CHANNEL_ID . "/messages?limit=20&after={$last_message_id}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bot ' . DISCORD_BOT_TOKEN,
        'Content-Type: application/json',
        'User-Agent: GoldOSRS/1.0',
    ],
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200 || !$response) {
    log_error("Discord listener: HTTP {$httpcode}");
    unlink($lock);
    exit;
}

$messages = json_decode($response, true);
if (!is_array($messages) || empty($messages)) {
    unlink($lock);
    exit;
}

// Sort oldest first
usort($messages, fn($a, $b) => strcmp($a['id'], $b['id']));
$webhook_app_id = $cache['webhook_app_id'] ?? null;

foreach ($messages as $msg) {
    $msg_id     = $msg['id'];
    $author     = $msg['author'] ?? [];
    $content    = trim($msg['content'] ?? '');
    $is_bot     = !empty($author['bot']);

    // Skip bots and webhook messages (those are our own outgoing messages)
    if ($is_bot || empty($content)) continue;

    // Parse session ID from message thread / content
    // Expected admin reply format: anything NOT starting with [User:
    // Messages from our webhook start with [User: or [Admin: — skip those
    if (str_starts_with($content, '[User:') || str_starts_with($content, '[Admin:') || str_starts_with($content, '🆕') || str_starts_with($content, '🛒') || str_starts_with($content, '✅') || str_starts_with($content, '💰') || str_starts_with($content, '₿') || str_starts_with($content, '🎲')) {
        continue;
    }

    // Try to detect session ID from content like "reply #42 message"
    // or from channel thread reference
    preg_match('/#(\d+)/i', $content, $m);
    $session_id = isset($m[1]) ? (int)$m[1] : null;

    // If no session ID detected, find most recent open session
    if (!$session_id) {
        $latest = db_one("SELECT id FROM chat_sessions WHERE status='open' ORDER BY last_activity DESC LIMIT 1");
        $session_id = $latest['id'] ?? null;
    }

    if (!$session_id) continue;

    $session = db_one('SELECT id FROM chat_sessions WHERE id=?', 'i', $session_id);
    if (!$session) continue;

    // Insert as admin message
    $sender_name = $author['username'] ?? 'Support';
    // Strip the "reply #N " prefix if present
    $clean_content = preg_replace('/^reply\s*#\d+\s*/i', '', $content);

    db_insert('INSERT INTO chat_messages (session_id, sender, sender_name, message) VALUES (?,?,?,?)',
        'isss', $session_id, 'discord', $sender_name, $clean_content);
    db_exec('UPDATE chat_sessions SET last_activity=NOW() WHERE id=?', 'i', $session_id);

    log_info("Discord message from {$sender_name} → session #{$session_id}: " . mb_substr($clean_content, 0, 80));
    $last_message_id = $msg_id;
}

// Save last processed message ID
file_put_contents(DISCORD_CACHE_FILE, json_encode(['last_message_id' => $last_message_id]));
unlink($lock);
