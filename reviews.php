<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'Reviews | GoldOSRS — 4.9★ Trustpilot';
$page_desc  = 'Read verified GoldOSRS reviews from real customers. 4.9 stars on Trustpilot with 2,400+ reviews.';
require_once __DIR__ . '/includes/header.php';
$reviews = [
  ['stars'=>5,'text'=>'Got 500M GP in 3 minutes. Fastest delivery I\'ve ever seen. Will order again.','author'=>'DragonSlayer99','service'=>'OSRS Gold · 500M'],
  ['stars'=>5,'text'=>'Inferno Cape done in under 3 hours! Super professional team. Account was fully safe.','author'=>'Zulrah_Master','service'=>'Inferno Cape Service'],
  ['stars'=>5,'text'=>'Quest cape done in 2 days! Brilliant support throughout the whole process.','author'=>'AzureLord7','service'=>'Quest Cape Service'],
  ['stars'=>5,'text'=>'Traded 1B OSRS → RS3 seamlessly. Instant swap, great rate.','author'=>'IronBtwPlayer','service'=>'OSRS → RS3 Swap'],
  ['stars'=>5,'text'=>'Bought a near-maxed account. Exactly as described. Very smooth handover.','author'=>'GrandMasterPK','service'=>'Near-Maxed Account'],
  ['stars'=>5,'text'=>'NMZ points grind done overnight. Woke up with all the points I needed!','author'=>'MaxedCombat55','service'=>'NMZ Boost'],
  ['stars'=>5,'text'=>'RS3 gold arrived in 4 minutes. Cheapest rates I found after checking 5 sites.','author'=>'RS3_Legend','service'=>'RS3 Gold · 5B'],
  ['stars'=>5,'text'=>'Needed 70 base stats for a boss service. Done perfectly, no issues at all.','author'=>'BossKiller2k','service'=>'Power Levelling'],
  ['stars'=>4,'text'=>'Great service. Slight delay on delivery but team communicated well throughout.','author'=>'PvM_Hero','service'=>'Boss Service — CoX'],
];
?>
<main class="page-content">
<section class="page-hero">
  <h1>⭐ Reviews</h1>
  <p>4.9 stars on Trustpilot · 2,400+ verified reviews</p>
</section>
<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="trustpilot-block mb-32">
      <div class="trustpilot-stars">★★★★★</div>
      <div class="trustpilot-score">4.9 / 5</div>
      <div class="trustpilot-count">2,400+ Verified Reviews</div>
    </div>
    <div class="grid-3">
      <?php foreach ($reviews as $r): ?>
      <div class="review-card">
        <div class="review-stars"><?= str_repeat('★', $r['stars']) . str_repeat('☆', 5-$r['stars']) ?></div>
        <p class="review-text">"<?= h($r['text']) ?>"</p>
        <div class="review-author"><?= h($r['author']) ?></div>
        <div class="review-service"><?= h($r['service']) ?></div>
        <div class="review-verified">✅ Verified Purchase</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
