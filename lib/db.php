<?php
// lib/db.php — mysqli connection (call db() to get connection)

$_db_conn = null;

function db(): mysqli {
    global $_db_conn;
    // PHP 8.4: mysqli::ping() is deprecated; check instance only
    if ($_db_conn instanceof mysqli) {
        return $_db_conn;
    }
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        log_error('DB connect failed: ' . $conn->connect_error);
        http_response_code(503);
        die('Service temporarily unavailable.');
    }
    $conn->set_charset('utf8mb4');
    // MySQL 8.0: enforce strict SQL mode for this connection
    if (!$conn->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")) {
        log_error('MySQL session config failed: ' . $conn->error);
    }
    $_db_conn = $conn;
    return $conn;
}

// Shorthand prepared query — returns mysqli_stmt on success or false on failure
function dbq(string $sql, string $types = '', ...$params) {
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        log_error('DB prepare failed: ' . db()->error . ' | SQL: ' . $sql);
        return false;
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Fetch all rows as assoc array
function db_all(string $sql, string $types = '', ...$params): array {
    $stmt = dbq($sql, $types, ...$params);
    if (!$stmt) return [];
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Fetch single row
function db_one(string $sql, string $types = '', ...$params): ?array {
    $stmt = dbq($sql, $types, ...$params);
    if (!$stmt) return null;
    $result = $stmt->get_result();
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

// Insert and return last insert ID
function db_insert(string $sql, string $types = '', ...$params): int {
    $stmt = dbq($sql, $types, ...$params);
    if (!$stmt) return 0;
    return (int) db()->insert_id;
}

// Execute (UPDATE/DELETE) and return affected rows
function db_exec(string $sql, string $types = '', ...$params): int {
    $stmt = dbq($sql, $types, ...$params);
    if (!$stmt) return 0;
    return $stmt->affected_rows;
}
