<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');

$action = post('action', get('action'));

if ($action === 'start') {
    $user = current_user();
    $guest_name  = post('guest_name', $user ? $user['username'] : 'Guest');
    $guest_email = post('guest_email', $user ? $user['email'] : '');
    $user_id     = $user ? (int)$user['id'] : null;
    $ip          = get_ip();

    // Check for existing open session
    if ($user_id) {
        $session = db_one('SELECT * FROM chat_sessions WHERE user_id=? AND status="open" ORDER BY id DESC LIMIT 1', 'i', $user_id);
    } else {
        $session = db_one('SELECT * FROM chat_sessions WHERE ip=? AND status="open" AND created_at > NOW()-INTERVAL 2 HOUR ORDER BY id DESC LIMIT 1', 's', $ip);
    }

    $is_new = false;
    if (!$session) {
        $is_new = true;
        $session_id = db_insert(
            'INSERT INTO chat_sessions (user_id, guest_name, guest_email, ip) VALUES (?,?,?,?)',
            'isss', $user_id, $guest_name, $guest_email, $ip
        );
        $session = db_one('SELECT * FROM chat_sessions WHERE id=?', 'i', $session_id);
    }

    $messages = db_all('SELECT * FROM chat_messages WHERE session_id=? ORDER BY id ASC LIMIT 50', 'i', $session['id']);
    json_out(['session_id' => $session['id'], 'is_new' => $is_new, 'messages' => $messages]);
}

if ($action === 'send') {
    csrf_check();
    if (!rate_limit('chat_send', 1)) { json_out(['error' => 'Too fast'], 429); }

    $session_id = (int)post('session_id');
    $message    = trim(post('message'));

    if (!$session_id || !$message) { json_out(['error' => 'Missing fields'], 400); }
    if (mb_strlen($message) > 1000) { json_out(['error' => 'Message too long'], 400); }

    $session = db_one('SELECT * FROM chat_sessions WHERE id=?', 'i', $session_id);
    if (!$session) { json_out(['error' => 'Session not found'], 404); }

    $user = current_user();
    $sender_name = $user ? $user['username'] : ($session['guest_name'] ?? 'Guest');

    $msg_id = db_insert(
        'INSERT INTO chat_messages (session_id, sender, sender_name, message) VALUES (?,?,?,?)',
        'isss', $session_id, 'user', $sender_name, $message
    );

    // Update session activity
    db_exec('UPDATE chat_sessions SET last_activity=NOW() WHERE id=?', 'i', $session_id);

    // Discord notify (only first message or every 5th)
    $msg_count = db_one('SELECT COUNT(*) AS c FROM chat_messages WHERE session_id=?', 'i', $session_id)['c'] ?? 0;
    if ($msg_count <= 1) {
        discord_notify_chat($session_id, $sender_name, $message);
    } else {
        // Still relay to Discord
        discord_send("[Chat #{$session_id} | {$sender_name}]: " . $message);
    }

    json_out(['success' => true, 'msg_id' => $msg_id]);
}

json_out(['error' => 'Unknown action'], 400);
