-- ════════════════════════════════════════════════════════════
-- GoldOSRS — Full Database Schema
-- MySQL 5.7+ compatible
-- Import: phpMyAdmin → your database → Import → select this file
-- ════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ── Users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`                  INT          AUTO_INCREMENT PRIMARY KEY,
  `username`            VARCHAR(32)  NOT NULL UNIQUE,
  `email`               VARCHAR(255) NOT NULL UNIQUE,
  `password`            VARCHAR(255) NOT NULL,
  `balance_osrs`        BIGINT       DEFAULT 0 COMMENT 'GP millions',
  `balance_rs3`         BIGINT       DEFAULT 0,
  `role`                ENUM('user','admin') DEFAULT 'user',
  `btc_deposit_address` VARCHAR(100) DEFAULT NULL,
  `referral_code`       VARCHAR(16)  DEFAULT NULL,
  `referred_by`         INT          DEFAULT NULL,
  `email_verified`      TINYINT(1)   DEFAULT 0,
  `verification_token`  VARCHAR(64)  DEFAULT NULL,
  `reset_token`         VARCHAR(64)  DEFAULT NULL,
  `reset_expires`       DATETIME     DEFAULT NULL,
  `login_streak`        INT          DEFAULT 0,
  `last_login`          DATE         DEFAULT NULL,
  `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_email`    (`email`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Orders ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT           AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT           DEFAULT NULL,
  `guest_email`    VARCHAR(255)  DEFAULT NULL,
  `guest_rsn`      VARCHAR(50)   DEFAULT NULL,
  `type`           ENUM('buy','sell','swap','service') NOT NULL,
  `service_type`   VARCHAR(100)  DEFAULT NULL,
  `game`           ENUM('osrs','rs3') DEFAULT 'osrs',
  `amount`         BIGINT        DEFAULT 0 COMMENT 'GP millions',
  `price_usd`      DECIMAL(10,2) DEFAULT 0,
  `btc_address`    VARCHAR(100)  DEFAULT NULL,
  `btc_amount`     DECIMAL(16,8) DEFAULT NULL,
  `btc_txid`       VARCHAR(100)  DEFAULT NULL,
  `payment_method` ENUM('crypto','card','paypal') DEFAULT 'crypto',
  `rsn`            VARCHAR(50)   DEFAULT NULL,
  `trade_method`   ENUM('face_to_face','grand_exchange','chest') DEFAULT 'face_to_face',
  `details`        TEXT          DEFAULT NULL,
  `status`         ENUM('pending','paid','processing','completed','cancelled','refunded') DEFAULT 'pending',
  `admin_notes`    TEXT          DEFAULT NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `paid_at`        TIMESTAMP     NULL DEFAULT NULL,
  `completed_at`   TIMESTAMP     NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `idx_status` (`status`),
  KEY `idx_user`   (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Chat Sessions ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id`                INT          AUTO_INCREMENT PRIMARY KEY,
  `user_id`           INT          DEFAULT NULL,
  `guest_name`        VARCHAR(50)  DEFAULT 'Guest',
  `guest_email`       VARCHAR(255) DEFAULT NULL,
  `ip`                VARCHAR(45)  DEFAULT NULL,
  `status`            ENUM('open','closed') DEFAULT 'open',
  `discord_thread_id` VARCHAR(100) DEFAULT NULL,
  `order_id`          INT          DEFAULT NULL,
  `last_activity`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Chat Messages ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id`            INT         AUTO_INCREMENT PRIMARY KEY,
  `session_id`    INT         NOT NULL,
  `sender`        ENUM('user','admin','discord') NOT NULL,
  `sender_name`   VARCHAR(50) DEFAULT NULL,
  `message`       TEXT        NOT NULL,
  `read_by_admin` TINYINT(1)  DEFAULT 0,
  `read_by_user`  TINYINT(1)  DEFAULT 0,
  `created_at`    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE,
  KEY `idx_session` (`session_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Games (Provably Fair Gambling) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `games` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT          NOT NULL,
  `game_type`   ENUM('dice','roulette','coinflip','blackjack','highlow','rs3dice') NOT NULL,
  `bet`         BIGINT       NOT NULL COMMENT 'GP millions',
  `multiplier`  DECIMAL(8,2) DEFAULT 1.00,
  `result`      VARCHAR(255) DEFAULT NULL,
  `win_amount`  BIGINT       DEFAULT 0,
  `won`         TINYINT(1)   DEFAULT 0,
  `server_seed` VARCHAR(64)  DEFAULT NULL,
  `server_hash` VARCHAR(64)  DEFAULT NULL,
  `client_seed` VARCHAR(64)  DEFAULT NULL,
  `nonce`       INT          DEFAULT 0,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`game_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Toasts ────────────────────────────────────────────────────────────────────
-- username column included here natively (no ALTER TABLE needed)
CREATE TABLE IF NOT EXISTS `toasts` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `type`       ENUM('real','simulated') DEFAULT 'simulated',
  `username`   VARCHAR(50)  DEFAULT NULL,
  `content`    VARCHAR(255) NOT NULL,
  `shown`      TINYINT(1)   DEFAULT 0,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_shown` (`shown`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Prices ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `prices` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(50)   NOT NULL UNIQUE,
  `value`      DECIMAL(10,4) NOT NULL,
  `label`      VARCHAR(100)  DEFAULT NULL,
  `updated_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Config ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `config` (
  `key`        VARCHAR(100) PRIMARY KEY,
  `value`      TEXT         NOT NULL,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Settings (required by core.php setting() function) ────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(100) NOT NULL UNIQUE,
  `value`      TEXT         NOT NULL DEFAULT '',
  `label`      VARCHAR(200) DEFAULT NULL,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Deposits ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `deposits` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT           NOT NULL,
  `currency`      ENUM('BTC','ETH','LTC') DEFAULT 'BTC',
  `address`       VARCHAR(100)  NOT NULL,
  `amount_crypto` DECIMAL(16,8) DEFAULT NULL,
  `amount_usd`    DECIMAL(10,2) DEFAULT NULL,
  `gp_credited`   BIGINT        DEFAULT 0,
  `txid`          VARCHAR(100)  DEFAULT NULL,
  `confirmations` INT           DEFAULT 0,
  `status`        ENUM('pending','confirmed','credited') DEFAULT 'pending',
  `created_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at`  TIMESTAMP     NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `idx_address` (`address`),
  KEY `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Withdrawals ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id`           INT         AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT         NOT NULL,
  `game`         ENUM('osrs','rs3') DEFAULT 'osrs',
  `amount`       BIGINT      NOT NULL COMMENT 'GP millions',
  `rsn`          VARCHAR(50) NOT NULL,
  `trade_method` VARCHAR(50) DEFAULT 'face_to_face',
  `status`       ENUM('pending','processing','completed','rejected') DEFAULT 'pending',
  `admin_notes`  TEXT        DEFAULT NULL,
  `created_at`   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Admin Log ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_log` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `admin_id`    INT          DEFAULT NULL,
  `action`      VARCHAR(255) NOT NULL,
  `target_type` VARCHAR(50)  DEFAULT NULL,
  `target_id`   INT          DEFAULT NULL,
  `details`     TEXT         DEFAULT NULL,
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Raffle Prizes ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `raffle_prizes` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `value`      BIGINT        NOT NULL COMMENT 'Prize value in GP millions',
  `added_date` DATE          NOT NULL,
  `active`     TINYINT(1)    DEFAULT 1,
  `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════
-- SEED DATA
-- ════════════════════════════════════════════════════════════

-- ── Default Prices ────────────────────────────────────────────────────────────
INSERT INTO `prices` (`key`, `value`, `label`) VALUES
  ('osrs_crypto', 0.26, 'OSRS Gold - Crypto (per M)'),
  ('osrs_card',   0.29, 'OSRS Gold - Card (per M)'),
  ('osrs_bulk',   0.24, 'OSRS Gold - Bulk 1B+ (per M)'),
  ('rs3_crypto',  0.05, 'RS3 Gold - Crypto (per M)'),
  ('rs3_card',    0.06, 'RS3 Gold - Card (per M)'),
  ('rs3_bulk',    0.04, 'RS3 Gold - Bulk 5B+ (per M)'),
  ('sell_osrs',   0.20, 'Sell OSRS Gold - Crypto (per M)'),
  ('sell_rs3',    0.04, 'Sell RS3 Gold (per M)'),
  ('swap_rate',   5.80, 'OSRS to RS3 swap ratio')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ── Default Config ────────────────────────────────────────────────────────────
INSERT INTO `config` (`key`, `value`) VALUES
  ('gambling_enabled',     '1'),
  ('min_bet_osrs',         '5'),
  ('max_bet_osrs',         '2000'),
  ('house_edge_dice',      '3'),
  ('house_edge_coinflip',  '5'),
  ('house_edge_blackjack', '2'),
  ('site_live',            '1')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ── Default Settings ─────────────────────────────────────────────────────────
INSERT INTO `settings` (`key`, `value`, `label`) VALUES
  ('site_name',            'GoldOSRS',             'Site Name'),
  ('site_url',             'https://goldosrs.com', 'Site URL'),
  ('site_email',           'support@goldosrs.com', 'Support Email'),
  ('site_live',            '1',                    'Site Live (0=maintenance)'),
  ('discord_webhook',      '',                     'Discord Webhook URL'),
  ('btc_address',          '',                     'Static BTC Address'),
  ('osrs_crypto',          '0.26',                 'OSRS Crypto Rate (per M)'),
  ('osrs_card',            '0.29',                 'OSRS Card Rate (per M)'),
  ('osrs_bulk',            '0.24',                 'OSRS Bulk Rate (per M)'),
  ('rs3_crypto',           '0.05',                 'RS3 Crypto Rate (per M)'),
  ('rs3_card',             '0.06',                 'RS3 Card Rate (per M)'),
  ('rs3_bulk',             '0.04',                 'RS3 Bulk Rate (per M)'),
  ('sell_osrs',            '0.20',                 'Buy OSRS Rate (per M)'),
  ('sell_rs3',             '0.04',                 'Buy RS3 Rate (per M)'),
  ('swap_rate',            '5.80',                 'OSRS to RS3 Swap Ratio'),
  ('gambling_enabled',     '1',                    'Gambling Enabled'),
  ('min_bet',              '5',                    'Min Bet (M GP)'),
  ('max_bet',              '2000',                 'Max Bet (M GP)'),
  ('house_edge_dice',      '3',                    'Dice House Edge %'),
  ('house_edge_coinflip',  '5',                    'Coinflip House Edge %'),
  ('house_edge_blackjack', '2',                    'Blackjack House Edge %'),
  ('toasts_enabled',       '1',                    'Toast Notifications'),
  ('admin_email',          'admin@goldosrs.com',   'Admin Email')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ── Default Admin Account ─────────────────────────────────────────────────────
-- Default password: password  ← CHANGE THIS immediately after import
-- To generate a new hash in PHP:
--   echo password_hash('yournewpassword', PASSWORD_BCRYPT);
INSERT IGNORE INTO `users`
  (`username`, `email`, `password`, `role`, `email_verified`)
VALUES
  ('admin', 'admin@goldosrs.com',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin', 1);

-- ── Seed Raffle Prizes ────────────────────────────────────────────────────────
INSERT INTO `raffle_prizes` (`name`, `value`, `added_date`) VALUES
  ('100M OSRS Gold',    100,  CURDATE()),
  ('500M OSRS Gold',    500,  CURDATE()),
  ('1B OSRS Gold',     1000,  CURDATE()),
  ('Inferno Cape Service', 5000, CURDATE()),
  ('500M RS3 Gold',     500,  CURDATE()),
  ('Quest Cape Service', 2000, CURDATE());

-- ── Seed Toasts ───────────────────────────────────────────────────────────────
INSERT INTO `toasts` (`type`, `username`, `content`) VALUES
  ('simulated', 'GoldOSRS', '🪙 Someone from UK just bought 500M OSRS Gold'),
  ('simulated', 'GoldOSRS', '⚔️ Dragon_Pro just ordered Inferno Cape Service'),
  ('simulated', 'GoldOSRS', '🪙 Someone from US just bought 1B OSRS Gold'),
  ('simulated', 'GoldOSRS', '🌸 MaxedMain just bought 2B OSRS Gold'),
  ('simulated', 'GoldOSRS', '⚔️ Someone from AU just ordered Quest Cape Service'),
  ('simulated', 'GoldOSRS', '🪙 Someone from DE just bought 200M RS3 Gold'),
  ('simulated', 'GoldOSRS', '⚔️ PvM_Legend just ordered Boss Service - ToB');

SET FOREIGN_KEY_CHECKS = 1;
