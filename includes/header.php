<?php
// includes/header.php
// $page_title, $page_desc, $page_keywords must be set before including
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../lib/functions.php';
}
$user = current_user();
$prices = get_prices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($page_title ?? 'GoldOSRS — Buy OSRS Gold, RS3 Gold & Services') ?></title>
<meta name="description" content="<?= h($page_desc ?? 'Buy cheap OSRS gold, RS3 gold, Inferno Cape, questing and power levelling services. Fast delivery, secure payments, 24/7 support.') ?>">
<meta name="keywords" content="<?= h($page_keywords ?? 'buy osrs gold, cheap runescape gold, rs3 gold, inferno cape service, osrs services') ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= h(SITE_URL . $_SERVER['REQUEST_URI']) ?>">
<!-- Open Graph -->
<meta property="og:title" content="<?= h($page_title ?? 'GoldOSRS') ?>">
<meta property="og:description" content="<?= h($page_desc ?? 'The Realm\'s Finest Marketplace') ?>">
<meta property="og:image" content="<?= SITE_URL ?>/assets/images/og-default.png">
<meta property="og:url" content="<?= h(SITE_URL . $_SERVER['REQUEST_URI']) ?>">
<meta property="og:type" content="website">
<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">
<link rel="apple-touch-icon" href="/assets/images/logo.svg">
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;500;600;700&family=MedievalSharp&display=swap" rel="stylesheet">
<!-- Styles -->
<link rel="stylesheet" href="/assets/css/style.css">
<?php if (!empty($extra_css)): ?>
<link rel="stylesheet" href="<?= h($extra_css) ?>">
<?php endif; ?>
</head>
<body>

<!-- Rune Rain -->
<div class="rune-rain" id="runeRain"></div>

<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
  <div class="loading-content">
    <img src="/assets/images/logo.svg" alt="GoldOSRS" class="loading-logo">
    <div class="loading-runes">ᚠᚢᚦᚨᚱᚲᚷᚹᚺᚾᛁᛃ</div>
    <div class="loading-text">Entering the Realm</div>
    <div class="loading-bar"><div class="loading-fill"></div></div>
  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Navigation -->
<nav class="navbar" id="navbar">
  <div class="nav-container">
    <a href="/" class="nav-logo">
      <img src="/assets/images/logo.svg" alt="GoldOSRS" width="42" height="42">
      <span class="nav-brand">
        <span class="brand-main">Gold OSRS</span>
        <span class="brand-sub">The Realm's Marketplace</span>
      </span>
    </a>

    <ul class="nav-links" id="navLinks">
      <li><a href="/" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? ' active' : '' ?>">Home</a></li>
      <li><a href="/services.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'services.php') ? ' active' : '' ?>">Services</a></li>
      <li><a href="/buy-gold.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'buy-gold.php') ? ' active' : '' ?>">Buy Gold</a></li>
      <li><a href="/sell-gold.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'sell-gold.php') ? ' active' : '' ?>">Sell Gold</a></li>
      <li><a href="/gambling.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'gambling.php') ? ' active' : '' ?>">Gambling</a></li>
      <li><a href="/raffle.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'raffle.php') ? ' active' : '' ?>">Raffle</a></li>
      <li><a href="/accounts.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'accounts.php') ? ' active' : '' ?>">Accounts</a></li>
      <li><a href="/reviews.php" class="nav-link<?= (basename($_SERVER['PHP_SELF']) === 'reviews.php') ? ' active' : '' ?>">Reviews</a></li>
    </ul>

    <div class="nav-auth" id="navAuth">
      <?php if ($user): ?>
        <a href="/dashboard.php" class="btn-dash">Dashboard</a>
        <a href="/logout.php" class="btn-login">Logout</a>
      <?php else: ?>
        <a href="/login.php" class="btn-login">Login</a>
        <a href="/register.php" class="btn-register">Register</a>
      <?php endif; ?>
    </div>

    <!-- Burger -->
    <button class="nav-burger" id="navBurger" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile Nav -->
<div class="mobile-nav" id="mobileNav">
  <a href="/" class="mob-link">🏠 Home</a>
  <a href="/services.php" class="mob-link">⚔️ Services</a>
  <a href="/buy-gold.php" class="mob-link">🪙 Buy Gold</a>
  <a href="/sell-gold.php" class="mob-link">💰 Sell Gold</a>
  <a href="/gambling.php" class="mob-link">🎲 Gambling</a>
  <a href="/raffle.php" class="mob-link">🎁 Raffle</a>
  <a href="/accounts.php" class="mob-link">👤 Accounts</a>
  <a href="/reviews.php" class="mob-link">⭐ Reviews</a>
  <a href="/faq.php" class="mob-link">❓ FAQ</a>
  <div class="mob-auth">
    <?php if ($user): ?>
      <a href="/dashboard.php" class="btn-dash">📊 Dashboard</a>
      <a href="/logout.php" class="btn-login">Logout</a>
    <?php else: ?>
      <a href="/login.php" class="btn-login">Login</a>
      <a href="/register.php" class="btn-register">Register</a>
    <?php endif; ?>
  </div>
</div>
<div class="nav-overlay" id="navOverlay"></div>
