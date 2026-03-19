<?php
/**
 * GoldOSRS.com – Site Configuration
 * Copy this file to config.php and fill in your real credentials.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'goldosrs_user');
define('DB_PASS', 'change_me_strong_password');
define('DB_NAME', 'goldosrs');
define('DB_PORT', 3306);

// Site settings
define('SITE_URL',   'https://goldosrs.com');
define('SITE_NAME',  'GoldOSRS');
define('SITE_EMAIL', 'support@goldosrs.com');

// Bitcoin deposit address (static; replace with your own)
define('BTC_ADDRESS', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa');

// Session name (change to something unique)
define('SESSION_NAME', 'goldosrs_sess');

// Password-reset token TTL in seconds (3600 = 1 hour)
define('RESET_TTL', 3600);

// Minimum bet amount in credits
define('MIN_BET', 1);

// House edge (percentage kept by house), e.g. 5 = 5%
define('HOUSE_EDGE', 5);
