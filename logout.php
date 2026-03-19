<?php
/**
 * GoldOSRS.com – Logout
 */

require_once __DIR__ . '/includes/functions.php';
start_session();

$_SESSION = [];
session_destroy();
header('Location: /login.php');
exit;
