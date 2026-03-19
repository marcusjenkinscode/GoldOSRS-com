<?php
/**
 * Site header – included at the top of every page.
 * $page_title must be set before including this file.
 */

require_once __DIR__ . '/functions.php';
start_session();

$flash       = flash_get();
$basket_cnt  = basket_count();
$logged_in   = is_logged_in();
$page_title  = isset($page_title) ? h($page_title) . ' – ' . SITE_NAME : SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <!-- Google Fonts: MedievalSharp feel -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=MedievalSharp&family=Cinzel:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <a class="nav-brand" href="/">
            <span class="gold-text">Gold</span><span class="white-text">OSRS</span>
        </a>
        <button class="nav-toggle" aria-label="Toggle navigation" onclick="toggleNav()">&#9776;</button>
        <ul class="nav-links" id="navLinks">
            <li><a href="/"          class="nav-link">Home</a></li>
            <li><a href="/order.php" class="nav-link">Order</a></li>
            <li><a href="/gambling.php" class="nav-link">Games</a></li>
            <li><a href="/raffle.php"   class="nav-link">Raffle</a></li>
            <?php if ($logged_in): ?>
            <li>
                <a href="/payment.php" class="nav-link basket-link">
                    🛒 Basket
                    <?php if ($basket_cnt > 0): ?>
                    <span class="basket-badge"><?= $basket_cnt ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="/logout.php" class="nav-link">Logout</a></li>
            <?php else: ?>
            <li><a href="/login.php"    class="nav-link">Login</a></li>
            <li><a href="/register.php" class="nav-link btn-gold">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="flash flash-<?= h($flash['type']) ?>">
    <?= h($flash['message']) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<main>
