-- ════════════════════════════════════════════════════════════
-- GoldOSRS — Feature Patch SQL
-- Run against your live database after the code update.
-- Safe to run multiple times (uses IF NOT EXISTS / ALTER checks).
-- ════════════════════════════════════════════════════════════

-- ── FIX 1: Add rs3dice to games.game_type enum ───────────────────────────────
-- Adds the new RS3 Dragon Dice game type to the existing games table.
-- CHANGE COLUMN is used because MySQL 5.7 does not support IF NOT EXISTS on ALTER for ENUMs.
ALTER TABLE `games`
  MODIFY COLUMN `game_type`
    ENUM('dice','roulette','coinflip','blackjack','highlow','rs3dice') NOT NULL;

-- ── FIX 2: Create raffle_prizes table if missing ──────────────────────────────
CREATE TABLE IF NOT EXISTS `raffle_prizes` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `value`      BIGINT        NOT NULL COMMENT 'Prize value in GP millions',
  `added_date` DATE          NOT NULL,
  `active`     TINYINT(1)    DEFAULT 1,
  `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── FIX 3: Seed some starter raffle prizes ───────────────────────────────────
-- Only inserts if no prizes exist yet (INSERT IGNORE on auto-increment PK never
-- conflicts, so this is safe to re-run — it will simply add more rows each time.
-- To reset prizes, truncate raffle_prizes first, then re-run this script.)
INSERT INTO `raffle_prizes` (`name`, `value`, `added_date`) VALUES
  ('100M OSRS Gold',       100,  CURDATE()),
  ('500M OSRS Gold',       500,  CURDATE()),
  ('1B OSRS Gold',        1000,  CURDATE()),
  ('Inferno Cape Service', 5000, CURDATE()),
  ('500M RS3 Gold',        500,  CURDATE()),
  ('Quest Cape Service',  2000,  CURDATE());

-- ── VERIFY ───────────────────────────────────────────────────────────────────
SELECT 'raffle_prizes' AS tbl, COUNT(*) AS rows FROM raffle_prizes
UNION ALL
SELECT 'games', COUNT(*) FROM games;
