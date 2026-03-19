<?php
// GoldOSRS Configuration — edit before deployment

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// ── Site ──────────────────────────────────────────────────────────────────────
define('SITE_URL', 'https://goldosrs.com');
define('SITE_NAME', 'GoldOSRS');
define('SITE_EMAIL', 'support@goldosrs.com');

// ── Discord ───────────────────────────────────────────────────────────────────
define('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN');
define('DISCORD_BOT_TOKEN', 'YOUR_BOT_TOKEN');         // For reading replies
define('DISCORD_CHANNEL_ID', 'YOUR_CHANNEL_ID');       // Channel for support chats
define('DISCORD_GUILD_ID', 'YOUR_GUILD_ID');

// ── Bitcoin / Crypto ──────────────────────────────────────────────────────────
define('STATIC_BTC_ADDRESS', 'YOUR_BTC_ADDRESS');  // Fallback if no Electrum
define('STATIC_ETH_ADDRESS', 'YOUR_ETH_ADDRESS');
define('STATIC_LTC_ADDRESS', 'YOUR_LTC_ADDRESS');
define('ELECTRUM_RPC_HOST', 'localhost');
define('ELECTRUM_RPC_PORT', 7777);
define('ELECTRUM_RPC_USER', 'user');
define('ELECTRUM_RPC_PASS', 'password');
define('BTC_CONFIRMATIONS_REQUIRED', 1);

// ── GP/USD conversion ─────────────────────────────────────────────────────────
// How many GP millions does $1 buy (rough, used for deposit crediting)
define('GP_PER_USD', 3.4);  // $1 = ~3.4M OSRS GP at $0.29/M

// ── Email (PHP mail() or SMTP) ────────────────────────────────────────────────
define('MAIL_FROM', 'noreply@goldosrs.com');
define('MAIL_FROM_NAME', 'GoldOSRS');

// ── Session / Security ────────────────────────────────────────────────────────
define('SESSION_NAME', 'goldosrs_sess');
define('RATE_LIMIT_SECONDS', 2);   // Min seconds between same actions

// ── Paths ─────────────────────────────────────────────────────────────────────
define('ROOT_PATH', __DIR__);
define('LOG_PATH', __DIR__ . '/logs');
define('DATA_PATH', __DIR__ . '/data');

// ── Discord cache file (for listener state) ───────────────────────────────────
define('DISCORD_CACHE_FILE', __DIR__ . '/data/discord_last.json');
