<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title    = 'OSRS Services — Inferno Cape, Questing, Levelling | GoldOSRS';
$page_desc     = 'Buy OSRS and RS3 services from veteran players. Inferno Cape, Fire Cape, questing, power levelling, boss services and more.';
$page_keywords = 'osrs services, inferno cape service, osrs questing service, power levelling osrs, boss service osrs';

// Show active promo banner if set
$promo_active = get_config('promo_active', '0');
$promo_pct    = (int)get_config('promo_pct', '0');
$promo_label  = get_config('promo_label', '');
$promo_code   = get_config('promo_code', '');

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>⚔️ Services</h1>
  <p>Every service completed by veteran players with thousands of hours.</p>
  <?php if ($promo_active === '1' && $promo_pct > 0): ?>
  <div style="margin-top:16px"><span class="promo-badge">🎁 <?= h($promo_label ?: "{$promo_pct}% off all services — code: {$promo_code}") ?></span></div>
  <?php endif; ?>
</section>
<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="grid-3">
      <div class="card"><div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000">🔥 Popular</div><div class="card-icon">🔥</div><h3>Inferno Cape</h3><p>100% completion guarantee or full refund. Account safety prioritized.</p><ul><li>100% completion guarantee</li><li>Full refund if unsuccessful</li><li>Account safety prioritized</li><li>VPN used at all times</li></ul><a href="/#order" onclick="prefillOrder('Inferno Cape Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">🛡️</div><h3>Fire Cape</h3><p>Guaranteed Fire Cape completion. Fast turnaround, account safety first.</p><ul><li>100% guarantee</li><li>All combat styles</li><li>Fast delivery</li></ul><a href="/#order" onclick="prefillOrder('Fire Cape Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">⚔️</div><h3>Questing</h3><p>Any quest completed — novice to grandmaster. Quest capes available.</p><ul><li>All quests available</li><li>Quest cape completion</li><li>Requirements included</li></ul><a href="/#order" onclick="prefillOrder('Questing Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">📈</div><h3>Power Levelling</h3><p>Blazing-fast skill training. Combat, skilling, 99s, maxing.</p><ul><li>All skills covered</li><li>99s &amp; Max cape</li><li>XP rate guaranteed</li></ul><a href="/#order" onclick="prefillOrder('Power Levelling Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">🏆</div><h3>Boss Services</h3><p>Raids, ToB, CoX, Nex, Zamorak — kills, KC boosts, unique farming.</p><ul><li>All raids available</li><li>Unique item farming</li><li>KC boost available</li></ul><a href="/#order" onclick="prefillOrder('Boss Services')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">🎯</div><h3>Minigame Boosts</h3><p>NMZ, Pest Control, Soul Wars, BA — any minigame reward or grind.</p><ul><li>All minigames</li><li>Points grinding</li><li>Reward unlocks</li></ul><a href="/#order" onclick="prefillOrder('Minigame Boost Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">📜</div><h3>Achievement Diaries</h3><p>Easy, medium, hard and elite diaries completed. All regions.</p><ul><li>All regions</li><li>All tiers</li><li>Requirements handled</li></ul><a href="/#order" onclick="prefillOrder('Achievement Diary Service')" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">👤</div><h3>Accounts for Sale</h3><p>Starters, pures, near-maxed, maxed mains. Verified &amp; secure.</p><ul><li>Starters &amp; pures</li><li>Near-maxed mains</li><li>Fully maxed builds</li></ul><a href="/accounts.php" class="btn-gold">Browse Accounts</a></div>
    </div>
  </div>
</section>
</main>
<script>
// Pre-populate the order form on the home page with the selected service
function prefillOrder(serviceName) {
  sessionStorage.setItem('prefill_service', serviceName);
}
// On page load, apply any stored prefill
(function() {
  const svc = sessionStorage.getItem('prefill_service');
  if (!svc) return;
  sessionStorage.removeItem('prefill_service');
  const sel = document.getElementById('orderServiceType') || document.querySelector('[name="service_type"]');
  if (sel) {
    for (let i = 0; i < sel.options.length; i++) {
      if (sel.options[i].text === svc || sel.options[i].value === svc) {
        sel.value = sel.options[i].value;
        sel.dispatchEvent(new Event('change'));
        break;
      }
    }
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

