<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'OSRS Accounts for Sale | GoldOSRS';
$page_desc  = 'Buy verified OSRS accounts. Starter pures, near-maxed, maxed mains. Secure handover.';
require_once __DIR__ . '/includes/header.php';
$accounts = [
  ['name'=>'Starter Pure','price'=>45,'icon'=>'⚔️','stats'=>'60 Atk / 99 Str / 1 Def','highlights'=>['F2P ready','Rune access','Clean history']],
  ['name'=>'Medium Level','price'=>120,'icon'=>'🛡️','stats'=>'70 Atk / 90 Str / 70 Def','highlights'=>['Members ready','Most quests done','100+ CB']],
  ['name'=>'Zerker Pure','price'=>180,'icon'=>'🏹','stats'=>'45 Def / 99 Str / 99 Range','highlights'=>['Barrows gloves','Recipe for Disaster','Ideal zerker stats']],
  ['name'=>'Maxed Combat','price'=>350,'icon'=>'⚔️','stats'=>'99 Atk / 99 Str / 99 Def / 99 HP','highlights'=>['99 Prayer','99 Magic','Full maxed combat']],
  ['name'=>'Near Maxed','price'=>480,'icon'=>'🌟','stats'=>'Most 99s — 2200+ total','highlights'=>['All hard diaries','Most quests','Fire cape']],
  ['name'=>'Fully Maxed','price'=>750,'icon'=>'👑','stats'=>'2277 Total — All 99s','highlights'=>['Max cape','Quest cape','Inferno cape']],
  ['name'=>'Ironman Starter','price'=>200,'icon'=>'🔒','stats'=>'Ironman — early progress','highlights'=>['Base stats done','Early quests','Clean ironman']],
  ['name'=>'GIM Ready','price'=>25,'icon'=>'👥','stats'=>'Fresh account','highlights'=>['Group IM ready','Email included','Instant delivery']],
];
?>
<main class="page-content">
<section class="page-hero">
  <h1>👤 Accounts for Sale</h1>
  <p>Verified OSRS accounts. Secure full handover with email included.</p>
</section>
<section class="section" style="padding-top:20px">
  <div class="container">
    <div class="grid-3">
      <?php foreach ($accounts as $a): ?>
      <div class="card">
        <div class="card-icon"><?= $a['icon'] ?></div>
        <h3><?= h($a['name']) ?></h3>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:10px"><?= h($a['stats']) ?></div>
        <ul><?php foreach ($a['highlights'] as $hl): ?><li><?= h($hl) ?></li><?php endforeach; ?></ul>
        <div style="margin:16px 0"><span class="price-amount">$<?= number_format($a['price'],2) ?></span></div>
        <a href="/#order" class="btn-gold">Buy Account</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
