<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$session_id = (int)get('session_id');
$last_id    = (int)get('last_id', '0');

if (!$session_id) { json_out(['messages' => []]); }

$session = db_one('SELECT id, status FROM chat_sessions WHERE id=?', 'i', $session_id);
if (!$session) { json_out(['messages' => [], 'error' => 'not_found']); }

$messages = db_all(
    'SELECT id, sender, sender_name, message, created_at FROM chat_messages WHERE session_id=? AND id>? ORDER BY id ASC LIMIT 20',
    'ii', $session_id, $last_id
);

// Mark admin/discord messages as read by user
if (!empty($messages)) {
    db_exec('UPDATE chat_messages SET read_by_user=1 WHERE session_id=? AND id>? AND sender IN ("admin","discord")', 'ii', $session_id, $last_id);
}

json_out(['messages' => $messages, 'session_status' => $session['status']]);
