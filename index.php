<?php
/**
 * GoldOSRS.com – Home Page
 */
$page_title = 'Buy OSRS Gold & RS3 Services';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ================================================================== -->
<!--  Hero section with rune-rain animation                              -->
<!-- ================================================================== -->
<section class="hero">
    <canvas id="runesCanvas" aria-hidden="true"></canvas>

    <div class="hero-content">
        <h1 class="hero-title gold-text">GoldOSRS</h1>
        <p class="hero-subtitle">
            The fastest &amp; most trusted marketplace for OSRS and RS3 gold,
            skills, quests, and accounts.
        </p>
        <div class="hero-actions">
            <a href="/order.php?service=osrs-gold&price=3.99" class="btn btn-gold btn-lg">
                ⚔&nbsp; Buy OSRS Gold
            </a>
            <a href="/order.php?service=rs3-gold&price=1.99" class="btn btn-outline btn-lg">
                🐉&nbsp; Buy RS3 Gold
            </a>
        </div>
    </div>
</section>

<!-- ================================================================== -->
<!--  Services grid                                                      -->
<!-- ================================================================== -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <div class="card-grid">

            <div class="card">
                <div class="card-icon">💰</div>
                <h3 class="card-title">OSRS Gold</h3>
                <p class="card-price">From $3.99 / M</p>
                <p class="card-desc">Instant delivery of Old School RuneScape gold. Safe and reliable.</p>
                <a href="/order.php?service=osrs-gold&price=3.99" class="btn btn-gold btn-sm">Order Now</a>
            </div>

            <div class="card">
                <div class="card-icon">🐉</div>
                <h3 class="card-title">RS3 Gold</h3>
                <p class="card-price">From $1.99 / M</p>
                <p class="card-desc">RuneScape 3 gold delivered fast – all worlds supported.</p>
                <a href="/order.php?service=rs3-gold&price=1.99" class="btn btn-gold btn-sm">Order Now</a>
            </div>

            <div class="card">
                <div class="card-icon">🎯</div>
                <h3 class="card-title">Quest Completion</h3>
                <p class="card-price">From $9.99</p>
                <p class="card-desc">Any quest completed by our expert team. Progress guaranteed.</p>
                <a href="/order.php?service=quest-completion&price=9.99" class="btn btn-gold btn-sm">Order Now</a>
            </div>

            <div class="card">
                <div class="card-icon">🏋️</div>
                <h3 class="card-title">Skill Training</h3>
                <p class="card-price">From $14.99</p>
                <p class="card-desc">Level up any skill efficiently. Custom goals welcome.</p>
                <a href="/order.php?service=skill-training&price=14.99" class="btn btn-gold btn-sm">Order Now</a>
            </div>

            <div class="card">
                <div class="card-icon">🎲</div>
                <h3 class="card-title">Gambling Games</h3>
                <p class="card-price">Win big with credits</p>
                <p class="card-desc">Dice, slots, RS3 gems – play fair provably-random games.</p>
                <a href="/gambling.php" class="btn btn-outline btn-sm">Play Now</a>
            </div>

            <div class="card">
                <div class="card-icon">🎁</div>
                <h3 class="card-title">Weekly Raffle</h3>
                <p class="card-price">Huge prize pool!</p>
                <p class="card-desc">Win rare items and gold in our weekly community raffle.</p>
                <a href="/raffle.php" class="btn btn-outline btn-sm">Enter Raffle</a>
            </div>

        </div>
    </div>
</section>

<!-- ================================================================== -->
<!--  Why choose us                                                      -->
<!-- ================================================================== -->
<section class="section" style="background:rgba(200,162,39,.04); border-top:1px solid #222; border-bottom:1px solid #222;">
    <div class="container">
        <h2 class="section-title">Why Choose GoldOSRS?</h2>
        <div class="card-grid">
            <div class="card">
                <div class="card-icon">⚡</div>
                <h3 class="card-title">Instant Delivery</h3>
                <p class="card-desc">Most orders completed within minutes. 24/7 availability.</p>
            </div>
            <div class="card">
                <div class="card-icon">🔒</div>
                <h3 class="card-title">100% Secure</h3>
                <p class="card-desc">No bans. We use safe, undetectable methods for all services.</p>
            </div>
            <div class="card">
                <div class="card-icon">💬</div>
                <h3 class="card-title">24/7 Support</h3>
                <p class="card-desc">Live chat and Discord support around the clock.</p>
            </div>
            <div class="card">
                <div class="card-icon">⭐</div>
                <h3 class="card-title">5-Star Rated</h3>
                <p class="card-desc">Thousands of satisfied customers. Trusted since 2018.</p>
            </div>
        </div>
    </div>
</section>

<script src="/js/runes.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
