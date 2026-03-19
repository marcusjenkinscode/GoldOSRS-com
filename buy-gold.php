<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title    = 'Buy OSRS Gold | Cheap RS3 Gold | Live Prices | GoldOSRS';
$page_desc     = 'Buy cheap OSRS and RS3 gold with fast delivery. Crypto 5% discount. Live prices updated constantly.';
$page_keywords = 'buy osrs gold, cheap osrs gold price, buy rs3 gold, runescape gold cheap';
$prices = get_prices();
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>Buy OSRS &amp; RS3 Gold</h1>
  <p>Live market rates. Crypto orders get 5% discount. Instant delivery.</p>
</section>
<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="tabs">
      <button class="tab-btn active" data-tab="osrs">OSRS Gold</button>
      <button class="tab-btn" data-tab="rs3">RS3 Gold</button>
      <button class="tab-btn" data-tab="swap">Swap Rates</button>
    </div>
    <div class="tabs-wrap">
      <div class="tab-panel active grid-3" data-tab="osrs">
        <div class="card"><div class="card-badge">Popular</div><div class="card-icon">🪙</div><h3>OSRS Gold — Crypto</h3><span class="price-amount">$<?= number_format($prices['osrs_crypto']??0.26,2) ?>/M</span><p class="price-label">5% crypto discount</p><a href="/#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-icon">💳</div><h3>OSRS Gold — Card</h3><span class="price-amount">$<?= number_format($prices['osrs_card']??0.29,2) ?>/M</span><p class="price-label">Visa, Mastercard, PayPal</p><a href="/#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-badge">Best Value</div><div class="card-icon">⚡</div><h3>Bulk OSRS (1B+)</h3><span class="price-amount">$<?= number_format($prices['osrs_bulk']??0.24,2) ?>/M</span><p class="price-label">Bulk discount rate</p><a href="/#order" class="btn-gold mt-16">Contact Us</a></div>
      </div>
      <div class="tab-panel grid-3" data-tab="rs3">
        <div class="card"><div class="card-badge">Popular</div><div class="card-icon">🪙</div><h3>RS3 Gold — Crypto</h3><span class="price-amount">$<?= number_format($prices['rs3_crypto']??0.05,2) ?>/M</span><p class="price-label">5% crypto discount</p><a href="/#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-icon">💳</div><h3>RS3 Gold — Card</h3><span class="price-amount">$<?= number_format($prices['rs3_card']??0.06,2) ?>/M</span><p class="price-label">Visa, Mastercard, PayPal</p><a href="/#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-badge">Best Value</div><div class="card-icon">⚡</div><h3>Bulk RS3 (5B+)</h3><span class="price-amount">$<?= number_format($prices['rs3_bulk']??0.04,2) ?>/M</span><p class="price-label">Bulk discount rate</p><a href="/#order" class="btn-gold mt-16">Contact Us</a></div>
      </div>
      <div class="tab-panel grid-3" data-tab="swap">
        <div class="card"><div class="card-icon">🔄</div><h3>OSRS → RS3</h3><span class="price-amount">1M OSRS ≈ <?= number_format($prices['swap_rate']??5.8,1) ?>M RS3</span><a href="/#order" class="btn-gold mt-16">Swap Now</a></div>
        <div class="card"><div class="card-icon">🔄</div><h3>RS3 → OSRS</h3><span class="price-amount"><?= number_format($prices['swap_rate']??5.8,1) ?>M RS3 ≈ 1M OSRS</span><a href="/#order" class="btn-gold mt-16">Swap Now</a></div>
        <div class="card"><div class="card-icon">💬</div><h3>Bulk Swap</h3><span class="price-amount">Custom Rates</span><a href="#" data-open-chat class="btn-gold mt-16">Contact Us</a></div>
      </div>
    </div>
    <!-- Calculator -->
    <div class="card mt-32" style="max-width:480px;margin:32px auto 0">
      <h3 class="text-gold mb-16">💰 Price Calculator</h3>
      <div class="form-group"><label>Amount (M GP)</label><input type="number" id="calcAmount" value="100" min="1" placeholder="e.g. 500"></div>
      <div class="form-group"><label>Type</label>
        <select id="calcMethod">
          <option value="osrs_crypto">OSRS — Crypto</option>
          <option value="osrs_card">OSRS — Card</option>
          <option value="osrs_bulk">OSRS — Bulk</option>
          <option value="rs3_crypto">RS3 — Crypto</option>
          <option value="rs3_card">RS3 — Card</option>
        </select>
      </div>
      <div style="font-size:28px;font-weight:700;color:var(--gold);text-align:center;padding:12px 0">$<span id="calcResult">26.00</span></div>
      <a href="/#order" class="btn-primary btn-full">Order This Amount</a>
    </div>
    <script>window.GOLD_PRICES = <?= json_encode($prices) ?>;</script>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
