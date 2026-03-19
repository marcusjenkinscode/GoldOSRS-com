# GoldOSRS — Shared Hosting Setup Guide (PHP 8.4 · MySQL 8.0)

## Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP         | 8.1     | **8.4**     |
| MySQL       | 8.0     | **8.0**     |
| Apache      | 2.4     | 2.4         |
| PHP Extensions | `mysqli`, `curl`, `json`, `mbstring` | same |

> **⚠️ IONOS Users:** Select **PHP 8.4** in the IONOS Control Panel → Hosting → PHP tile.
> MySQL 8.0 is the default database engine on IONOS — no action needed.

---

## IONOS Shared Hosting — Quick Start

On IONOS, PHP runs as **FastCGI/PHP-FPM** (not as an Apache module). PHP settings are
controlled by the included **`.user.ini`** file — upload it alongside `.htaccess`.

1. Upload all files via FTP (enable hidden files so `.htaccess` and `.user.ini` are included)
2. In the IONOS Control Panel → **Hosting** → **PHP tile** → select **PHP 8.4** → Save
3. Wait 2–3 minutes for the PHP version change to propagate
4. Continue with Step 2 (database) below

---

## Step 1 — Upload Files

1. Log in to cPanel → **File Manager**
2. Navigate to `public_html/` (or your domain's root folder)
3. Upload **all** files and folders from this repository, keeping the directory structure exactly as-is
4. **Important:** Enable "Show Hidden Files" in File Manager settings so `.htaccess` is visible and uploaded

Alternatively, use FTP (FileZilla, Cyberduck, etc.) — hidden files are shown by default.

---

## Step 2 — Create MySQL Database

1. cPanel → **MySQL Databases**
2. Under "Create New Database", enter a name (e.g. `goldosrs`) → click **Create Database**
3. Under "MySQL Users" → "Add New User", create a user with a **strong password** → click **Create User**
4. Under "Add User to Database", select your new user and database → click **Add** → grant **ALL PRIVILEGES**
5. Note down the full names — on cPanel, they are prefixed with your username (e.g. `cpuser_goldosrs`, `cpuser_dbuser`)

### Import the SQL file

1. cPanel → **phpMyAdmin** → click your new database in the left panel
2. Click the **Import** tab at the top
3. Click "Choose File" and select `db.sql` from your computer
4. Leave all settings at their defaults → click **Go**
5. You should see "Import has been successfully finished"

> **Note:** If you see any errors during import, make sure your MySQL version is 8.0 or higher.
> The `patch_errors.sql` and `patch_features.sql` files are for upgrading an **existing** database only — do **not** import them on a fresh install.

---

## Step 3 — Configure config.php

Open `config.php` in File Manager (Edit) or via FTP and fill in your details:

```php
// ── Database ─────────────────────────────────────────────
// Use the FULL names as shown in cPanel MySQL Databases
define('DB_HOST', 'localhost');
define('DB_USER', 'cpanelusername_dbuser');   // e.g. mysite_golduser
define('DB_PASS', 'your_strong_password');
define('DB_NAME', 'cpanelusername_goldosrs'); // e.g. mysite_goldosrs

// ── Site ─────────────────────────────────────────────────
define('SITE_URL', 'https://yourdomain.com'); // No trailing slash
define('SITE_EMAIL', 'support@yourdomain.com');

// ── Discord (optional — leave placeholders if not using) ─
define('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/...');

// ── Bitcoin (optional — leave placeholder for manual payments) ─
define('STATIC_BTC_ADDRESS', 'bc1q...');
```

> **Tip:** For `DB_USER` and `DB_NAME`, the full names are shown in cPanel → MySQL Databases under "Current Databases" and "Current Users".

---

## Step 4 — Create Required Folders

The site writes log files and caches to two folders. Create them if they do not exist:

1. cPanel → File Manager → navigate into `public_html/`
2. Click "New Folder" and create: `logs`
3. Click "New Folder" and create: `data`
4. Right-click each folder → **Permissions** → set to `755`

> The site will also try to create these automatically on first load, but creating them manually ensures correct permissions.

---

## Step 5 — Verify .htaccess and .user.ini are Uploaded

The `.htaccess` and `.user.ini` files are **hidden** by default in File Manager.

1. In File Manager, click **Settings** (top right) → enable "Show Hidden Files (dotfiles)"
2. Confirm `.htaccess` **and** `.user.ini` both exist in `public_html/`
3. If either is missing, re-upload it from the repository

> **If you get a 500 error immediately after upload:** The `.htaccess` file is often the cause.
> Temporarily rename it to `htaccess.bak` in File Manager. If the site loads (without clean URLs),
> the `.htaccess` has a problem — see Troubleshooting below.
>
> **IONOS users:** Also ensure `.user.ini` is uploaded — this file configures PHP 8.4 settings for
> IONOS FastCGI/PHP-FPM. PHP settings in `.htaccess` are ignored on IONOS (PHP runs as FastCGI).

---

## Step 6 — Test the Site

Visit your domain in a browser. You should see the GoldOSRS homepage.

If you see a **500 Internal Server Error**, see the Troubleshooting section below.

---

## Step 7 — Change Admin Password ⚠️

The default admin account is:
- **Username:** `admin`
- **Password:** `password`

**Change this immediately** — log in at `/login.php` then go to `/settings.php`.

---

## Step 8 — Set Up Cron Jobs (Optional)

cPanel → **Cron Jobs**. First find your PHP path using cPanel Terminal: `which php`

```
# BTC payment checker — every minute
* * * * * /usr/bin/php /home/USERNAME/public_html/cron/check_btc.php >> /dev/null 2>&1

# Toast notification generator — every minute
* * * * * /usr/bin/php /home/USERNAME/public_html/cron/toasts.php >> /dev/null 2>&1

# Discord reply listener — every minute (only if Discord bot is configured)
* * * * * /usr/bin/php /home/USERNAME/public_html/cron/discord_listener.php >> /dev/null 2>&1
```

Replace `USERNAME` with your cPanel username and `/usr/bin/php` with the path from `which php`.

---

## Step 9 — SSL Certificate

Make sure SSL is active on your domain (cPanel → **Let's Encrypt SSL** or **SSL/TLS** → install a free certificate).

Once SSL is active, the `.htaccess` will automatically redirect all HTTP traffic to HTTPS.

> **If you do not have SSL yet** and are getting redirect loops, temporarily comment out the HTTPS
> redirect lines in `.htaccess` (lines starting with `RewriteCond %{HTTPS}` and the `RewriteRule` below it).

---

## Step 10 — Discord Integration (Optional)

1. Go to your Discord server → Settings → Integrations → Webhooks
2. Create a webhook in your support channel → copy the URL
3. Paste it into `config.php` as `DISCORD_WEBHOOK_URL`
4. For two-way chat (admin replies via Discord), create a Bot at discord.com/developers
5. Add the Bot token, channel ID, and guild ID to `config.php`

---

## Step 11 — Bitcoin Payments

- **Simple mode** (recommended for most users): Set `STATIC_BTC_ADDRESS` in config.php.
  All orders show this one address. You manually verify payments and mark orders complete in `/admin/orders.php`.
- **Automatic mode**: Requires running the Electrum wallet daemon on a VPS/dedicated server.
  The cron job (`cron/check_btc.php`) checks the blockchain and auto-confirms payments.

---

## File Structure

```
public_html/
├── index.php          ← Homepage
├── login.php          ← Login
├── register.php       ← Register
├── dashboard.php      ← User dashboard
├── buy-gold.php       ← Buy gold page
├── sell-gold.php      ← Sell gold page
├── gambling.php       ← Gambling lobby
├── services.php       ← Services page
├── accounts.php       ← Accounts for sale
├── reviews.php        ← Reviews
├── faq.php            ← FAQ
├── deposit.php        ← Deposit / BTC payment
├── withdraw.php       ← Withdraw GP
├── history.php        ← Order + game history
├── settings.php       ← Account settings
├── terms.php          ← Terms of Service
├── privacy.php        ← Privacy Policy
├── forgot.php         ← Forgot password
├── reset.php          ← Password reset
├── logout.php         ← Logout
├── config.php         ← ⚠️ CONFIGURE THIS FIRST
├── db.sql             ← Import to MySQL (fresh install)
├── patch_errors.sql   ← Run only when upgrading an existing DB
├── patch_features.sql ← Run only when upgrading an existing DB
├── .htaccess          ← Security + URL rewriting (hidden file — must be uploaded)
├── .user.ini          ← PHP 8.4 settings for IONOS FastCGI (hidden file — must be uploaded)
├── robots.txt
├── admin/             ← Admin panel (admin role required)
│   ├── index.php      ← Dashboard
│   ├── chat.php       ← Live chat monitor
│   ├── orders.php     ← Order management
│   ├── users.php      ← User management
│   ├── gambling.php   ← Gambling stats
│   └── prices.php     ← Price editor
├── api/               ← AJAX endpoints
│   ├── chat_send.php
│   ├── chat_poll.php
│   ├── game_roll.php
│   ├── toasts.php
│   ├── check_payment.php
│   └── admin_reply.php
├── cron/              ← Background jobs (run via cPanel cron)
│   ├── check_btc.php
│   ├── discord_listener.php
│   └── toasts.php
├── lib/               ← Core libraries (not web-accessible)
│   ├── db.php
│   ├── functions.php
│   └── electrum.php
├── includes/          ← Page templates (not web-accessible)
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
│       ├── logo.svg
│       └── cursor.svg
├── logs/              ← Create this folder (chmod 755) — holds error logs
└── data/              ← Create this folder (chmod 755) — holds cache files
```

---

## Security Checklist

- [ ] Changed admin password from `password` to something strong
- [ ] Filled in all values in `config.php` (no placeholders remaining)
- [ ] SSL certificate active and HTTPS redirect working
- [ ] `logs/` and `data/` folders created with `755` permissions
- [ ] `db.sql` imported successfully with no errors
- [ ] `.htaccess` uploaded (hidden file — must enable "Show Hidden Files" in File Manager)
- [ ] `.user.ini` uploaded (hidden file — required for IONOS PHP-FPM settings)
- [ ] Cron jobs set up (if using BTC payments or Discord)

---

## Troubleshooting

### 500 Internal Server Error on every page

This is usually one of three things:

1. **Wrong PHP version** — On IONOS: Control Panel → Hosting → PHP tile → select **PHP 8.4**. On cPanel: MultiPHP Manager → set PHP 8.1+.

2. **`.htaccess` problem** — Rename `.htaccess` to `htaccess.bak` temporarily. If the site loads, the `.htaccess` is the issue. On IONOS, `LimitRequestBody` is removed from `.htaccess` in this release — make sure you have the latest version. Check that Apache `mod_rewrite` is enabled.

3. **PHP extension missing** — Your host's PHP build may be missing `mysqli` or `curl`. On IONOS the standard extensions (`mysqli`, `curl`, `json`, `mbstring`) are enabled by default on PHP 8.4. On cPanel: "Select PHP Version" → "Extensions" and ensure they are ticked.

### 500 Error on IONOS specifically

- Make sure `.user.ini` was uploaded alongside `.htaccess` (it's a hidden file — enable "show dotfiles" in your FTP client)
- IONOS uses PHP-FPM (FastCGI) — the PHP settings in `.user.ini` apply within ~5 minutes
- Check IONOS Control Panel → Hosting → Logs for Apache error details

### Blank white page (no error shown)

PHP is running but crashing silently. To see the error:
- Temporarily add these two lines to the very top of `index.php` (remove after debugging):
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Or check `logs/php_error.log` in File Manager (if the `logs/` folder was created with correct permissions).

### "Service temporarily unavailable" message

The database connection failed. Double-check:
- `DB_USER`, `DB_PASS`, `DB_NAME` in `config.php` are the full cPanel-prefixed names
- The user has ALL PRIVILEGES on the database
- `DB_HOST` is `localhost` (correct for almost all cPanel hosts)

### Database import errors in phpMyAdmin

- If you see "Table already exists" errors, the import ran before — you can safely ignore these if the site works
- If you see a syntax error on the `raffle_prizes` table, you may be on MySQL 5.6 or older — upgrade MySQL or ask your host to enable MySQL 5.7+

### Redirect loop / ERR_TOO_MANY_REDIRECTS

Your SSL is terminating at a proxy (CloudFlare, load balancer) before reaching Apache. Replace the HTTPS redirect block in `.htaccess` with this version that checks the `X-Forwarded-Proto` header:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
This redirects only when both Apache sees HTTP **and** the proxy header also indicates HTTP — preventing loops behind HTTPS proxies.

### Cron not running

- Use the full path to PHP: run `which php` in cPanel Terminal
- Confirm the cron output redirection (`>> /dev/null 2>&1`) — without it, cPanel may email you on every run
- Check the cron job log in cPanel → Cron Jobs → "Current Cron Jobs"

### Chat not working

Check that `logs/` and `data/` folders exist and are writable by PHP (permission `755` or `777`).
Check `logs/error.log` for specific error messages.
