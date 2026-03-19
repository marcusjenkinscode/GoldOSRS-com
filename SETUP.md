# GoldOSRS — cPanel Shared Hosting Setup Guide

## 1. Upload Files
Upload ALL files to your `public_html/` directory via cPanel File Manager or FTP.
Keep directory structure exactly as-is.

## 2. Create MySQL Database
1. cPanel → MySQL Databases → Create Database: `goldosrs`
2. Create a MySQL user and set a strong password
3. Add the user to the database with ALL PRIVILEGES
4. Import `db.sql` via phpMyAdmin

## 3. Configure config.php
Edit `/config.php` and fill in:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'cpanelusername_dbuser');
define('DB_PASS', 'your_strong_password');
define('DB_NAME', 'cpanelusername_goldosrs');
define('SITE_URL', 'https://goldosrs.com');
define('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/...');
define('STATIC_BTC_ADDRESS', 'bc1q...');
```

## 4. Set Folder Permissions
```
logs/     → 755 (writable by PHP)
data/     → 755 (writable by PHP)
```
Create these folders if they don't exist in cPanel File Manager.

## 5. Set Up Cron Jobs (cPanel → Cron Jobs)
```
# BTC payment checker — every minute
* * * * * php /home/USERNAME/public_html/cron/check_btc.php

# Toast generator — every minute
* * * * * php /home/USERNAME/public_html/cron/toasts.php

# Discord listener — every minute
* * * * * php /home/USERNAME/public_html/cron/discord_listener.php
```
Replace `USERNAME` with your actual cPanel username.

## 6. Change Admin Password
The default admin account is:
- Username: `admin`
- Password: `password` (CHANGE THIS IMMEDIATELY)

Login at `/login.php` then go to `/settings.php` to change password.

## 7. Discord Integration (Optional but recommended)
1. Go to your Discord server → Settings → Integrations → Webhooks
2. Create a webhook in your support channel
3. Copy the URL into `config.php` as `DISCORD_WEBHOOK_URL`
4. For two-way chat (reading Discord replies), create a Bot at discord.com/developers
5. Add Bot token and channel ID to `config.php`

## 8. Bitcoin Payments
- **Simple mode**: Set `STATIC_BTC_ADDRESS` in config.php. All orders use this one address.
  You manually verify payments and mark orders paid in `/admin/orders.php`.
- **Automatic mode**: Run Electrum wallet daemon on your server.
  The cron job checks blockchain.info API and auto-confirms payments.

## 9. SSL Certificate
Make sure SSL is active in cPanel (Let's Encrypt is free).
The `.htaccess` forces HTTPS automatically once SSL is installed.

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
├── db.sql             ← Import to MySQL
├── .htaccess          ← Security + URL rewriting
├── robots.txt
├── admin/             ← Admin panel (role protected)
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
├── cron/              ← Background jobs
│   ├── check_btc.php
│   ├── discord_listener.php
│   └── toasts.php
├── lib/               ← Core libraries
│   ├── db.php
│   ├── functions.php
│   └── electrum.php
├── includes/          ← Page templates
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
│       ├── logo.svg
│       └── cursor.svg
├── logs/              ← Create this folder (chmod 755)
└── data/              ← Create this folder (chmod 755)
```

## Security Checklist
- [ ] Changed admin password
- [ ] Updated all values in config.php
- [ ] SSL certificate active
- [ ] logs/ and data/ folders created with 755 permissions
- [ ] db.sql imported successfully
- [ ] Cron jobs set up
- [ ] .htaccess is uploaded (may be hidden — enable "Show Hidden Files" in File Manager)

## Troubleshooting
- **Blank page**: Check logs/php_error.log or enable display_errors temporarily in config.php
- **DB errors**: Verify DB credentials in config.php match exactly what cPanel shows
- **Cron not running**: Use full path to PHP: `which php` in cPanel Terminal to find it
- **Chat not working**: Check that logs/ and data/ are writable by PHP (chmod 755)
