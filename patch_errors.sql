-- ════════════════════════════════════════════════════════════
-- GoldOSRS / BlockHasher — ERROR PATCH
-- Run this in phpMyAdmin against your live database (dbs15122568)
-- Fixes all errors from php_errors.log
-- ════════════════════════════════════════════════════════════

-- ── FIX 1: Create missing `settings` table ────────────────────────────────
-- Error: Table 'dbs15122568.settings' doesn't exist
-- The old core.php calls setting() which reads from this table.

CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(100) NOT NULL UNIQUE,
  `value`      TEXT NOT NULL DEFAULT '',
  `label`      VARCHAR(200) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Seed all settings the old codebase expects
INSERT INTO `settings` (`key`, `value`, `label`) VALUES
  ('site_name',            'GoldOSRS',              'Site Name'),
  ('site_url',             'https://goldosrs.com',  'Site URL'),
  ('site_email',           'support@goldosrs.com',  'Support Email'),
  ('site_live',            '1',                     'Site Live (0=maintenance)'),
  ('discord_webhook',      '',                      'Discord Webhook URL'),
  ('btc_address',          '',                      'Static BTC Address'),
  ('osrs_crypto',          '0.26',                  'OSRS Gold Crypto Rate (per M)'),
  ('osrs_card',            '0.29',                  'OSRS Gold Card Rate (per M)'),
  ('osrs_bulk',            '0.24',                  'OSRS Gold Bulk Rate (per M)'),
  ('rs3_crypto',           '0.05',                  'RS3 Gold Crypto Rate (per M)'),
  ('rs3_card',             '0.06',                  'RS3 Gold Card Rate (per M)'),
  ('rs3_bulk',             '0.04',                  'RS3 Gold Bulk Rate (per M)'),
  ('sell_osrs',            '0.20',                  'Buy OSRS Rate (per M)'),
  ('sell_rs3',             '0.04',                  'Buy RS3 Rate (per M)'),
  ('swap_rate',            '5.80',                  'OSRS→RS3 Swap Ratio'),
  ('gambling_enabled',     '1',                     'Gambling Enabled'),
  ('min_bet',              '5',                     'Min Bet (M GP)'),
  ('max_bet',              '2000',                  'Max Bet (M GP)'),
  ('house_edge_dice',      '3',                     'Dice House Edge %'),
  ('house_edge_coinflip',  '5',                     'Coinflip House Edge %'),
  ('house_edge_blackjack', '2',                     'Blackjack House Edge %'),
  ('toasts_enabled',       '1',                     'Toast Notifications On/Off'),
  ('admin_email',          'admin@goldosrs.com',    'Admin Email')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);


-- ── FIX 2: Add missing `username` column to `toasts` table ───────────────
-- Error: Unknown column 'username' in 'field list' in api/toasts.php
-- MySQL 5.7 compatible — no IF NOT EXISTS on ADD COLUMN

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'toasts'
    AND COLUMN_NAME  = 'username'
);

SET @sql = IF(
  @col_exists = 0,
  'ALTER TABLE `toasts` ADD COLUMN `username` VARCHAR(50) DEFAULT NULL AFTER `type`',
  'SELECT "username column already exists" AS note'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill existing rows with a placeholder so they still display
UPDATE `toasts` SET `username` = 'GoldOSRS' WHERE `username` IS NULL;


-- ── FIX 3: No SQL needed for coinflip.php null warnings ──────────────────
-- Error: Trying to access array offset on null / number_format(null)
-- This is a PHP-side null-check bug in gambling/coinflip.php lines 12 & 15.
-- The fix is in the PHP file — see PATCH note below.
-- Root cause: the page reads $user['balance_osrs'] before confirming the
-- user is logged in, so $user is null for guests hitting the page directly.

-- ── VERIFY: Check both tables now exist and are seeded ───────────────────
SELECT 'settings' AS tbl, COUNT(*) AS rows FROM settings
UNION ALL
SELECT 'toasts',          COUNT(*)          FROM toasts
UNION ALL
SELECT 'config',          COUNT(*)          FROM config;
