<?php
/**
 * GoldOSRS.com – AJAX: Get Raffle Prizes
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo  = get_db();
    $rows = $pdo->query(
        'SELECT name, value FROM raffle_prizes ORDER BY value DESC'
    )->fetchAll();

    $prizes = array_map(function($r) {
        return [
            'name'      => $r['name'],
            'value_fmt' => format_gp((float)$r['value']),
        ];
    }, $rows);

    echo json_encode(['prizes' => $prizes]);
} catch (Throwable $e) {
    echo json_encode(['prizes' => [], 'error' => 'Could not load prizes.']);
}
