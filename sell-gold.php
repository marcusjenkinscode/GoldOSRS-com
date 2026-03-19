<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title    = 'Sell OSRS Gold | Sell RS3 Gold Fast | GoldOSRS';
$page_desc     = 'Sell your OSRS or RS3 gold fast. Instant PayPal, crypto and bank transfer payments.';
$page_keywords = 'sell osrs gold, sell rs3 gold, osrs gold seller, cash out runescape gold';
$prices = get_prices();
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>💰 Sell Your Gold</h1>
  <p>Fast payments via crypto, PayPal or bank transfer. Best rates guaranteed.</p>
</section>
<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="grid-3">
      <div class="card"><div class="card-badge">Best Rate</div><div class="card-icon">🪙</div><h3>Sell OSRS Gold — Crypto</h3><span class="price-amount">$<?= number_format($prices['sell_osrs']??0.20,2) ?>/M</span><p class="price-label">Paid instantly in BTC/ETH/LTC</p><a href="/#order" class="btn-gold mt-16">Sell Now</a></div>
      <div class="card"><div class="card-icon">💳</div><h3>Sell OSRS Gold — PayPal</h3><span class="price-amount">$<?= number_format(($prices['sell_osrs']??0.20)*0.9,2) ?>/M</span><p class="price-label">PayPal Friends &amp; Family</p><a href="/#order" class="btn-gold mt-16">Sell Now</a></div>
      <div class="card"><div class="card-icon">🎮</div><h3>Sell RS3 Gold</h3><span class="price-amount">$<?= number_format($prices['sell_rs3']??0.04,2) ?>/M</span><p class="price-label">Crypto payment</p><a href="/#order" class="btn-gold mt-16">Sell Now</a></div>
    </div>
    <div class="card mt-32" style="max-width:480px;margin:32px auto 0">
      <h3 class="text-gold mb-16">💵 Sell Calculator</h3>
      <div class="form-group"><label>Amount (M GP)</label><input type="number" id="sellAmount" value="100" min="1" oninput="calcSell()"></div>
      <div class="form-group"><label>Type</label>
        <select id="sellType" onchange="calcSell()">
          <option value="<?= $prices['sell_osrs']??0.20 ?>">OSRS Gold</option>
          <option value="<?= $prices['sell_rs3']??0.04 ?>">RS3 Gold</option>
        </select>
      </div>
      <div style="font-size:28px;font-weight:700;color:var(--gold);text-align:center;padding:12px 0">$<span id="sellResult">20.00</span></div>
      <a href="/#order" class="btn-primary btn-full">Start Selling</a>
    </div>
  </div>
</section>
<script>
function calcSell(){const a=parseFloat(document.getElementById('sellAmount').value)||0;const r=parseFloat(document.getElementById('sellType').value)||0.20;document.getElementById('sellResult').textContent=(a*r).toFixed(2);}
calcSell();
</script>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
