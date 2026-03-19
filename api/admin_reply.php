<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');

$admin = require_admin();
csrf_check();

$action     = post('action');
$session_id = (int)post('session_id');
$message    = trim(post('message'));

if ($action === 'reply') {
    if (!$session_id || !$message) { json_out(['error' => 'Missing fields'], 400); }
    if (mb_strlen($message) > 2000) { json_out(['error' => 'Message too long'], 400); }

    $session = db_one('SELECT * FROM chat_sessions WHERE id=?', 'i', $session_id);
    if (!$session) { json_out(['error' => 'Session not found'], 404); }

    db_insert('INSERT INTO chat_messages (session_id, sender, sender_name, message, read_by_user) VALUES (?,?,?,?,0)',
        'isss', $session_id, 'admin', $admin['username'], $message);
    db_exec('UPDATE chat_sessions SET last_activity=NOW() WHERE id=?', 'i', $session_id);
    admin_log($admin['id'], 'chat_reply', 'chat_session', $session_id);

    // Relay to Discord
    discord_send("[Admin: {$admin['username']} → Session #{$session_id}]: " . $message);

    json_out(['success' => true]);
}

if ($action === 'close_session') {
    db_exec('UPDATE chat_sessions SET status="closed" WHERE id=?', 'i', $session_id);
    admin_log($admin['id'], 'close_chat', 'chat_session', $session_id);
    json_out(['success' => true]);
}

if ($action === 'sessions') {
    $sessions = db_all(
        'SELECT cs.*, u.username,
         (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id=cs.id AND cm.read_by_admin=0 AND cm.sender="user") AS unread
         FROM chat_sessions cs
         LEFT JOIN users u ON u.id=cs.user_id
         ORDER BY cs.last_activity DESC LIMIT 50'
    );
    json_out(['sessions' => $sessions]);
}

if ($action === 'messages') {
    $msgs = db_all('SELECT * FROM chat_messages WHERE session_id=? ORDER BY id ASC', 'i', $session_id);
    db_exec('UPDATE chat_messages SET read_by_admin=1 WHERE session_id=?', 'i', $session_id);
    json_out(['messages' => $msgs]);
}

json_out(['error' => 'Unknown action'], 400);
