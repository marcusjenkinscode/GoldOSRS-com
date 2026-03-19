<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Return a random unshown toast or last real one
$toast = db_one('SELECT * FROM toasts WHERE shown=0 ORDER BY RAND() LIMIT 1');
if (!$toast) {
    $toast = db_one('SELECT * FROM toasts ORDER BY id DESC LIMIT 1');
}
if ($toast) {
    db_exec('UPDATE toasts SET shown=1 WHERE id=?', 'i', $toast['id']);
    json_out(['content' => $toast['content'], 'type' => $toast['type']]);
} else {
    json_out(['content' => null]);
}
