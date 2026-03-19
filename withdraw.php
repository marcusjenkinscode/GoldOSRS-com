<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$user = require_login();
$page_title = 'Withdraw | GoldOSRS';
$success = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $game   = post('game', 'osrs');
    $amount = (int)post('amount', 0);
    $rsn    = post('rsn');
    $method = post('trade_method', 'face_to_face');
    $col    = $game === 'rs3' ? 'balance_rs3' : 'balance_osrs';
    $bal    = (int)$user[$col];
    if (!$amount || $amount < 5)   { $error = 'Minimum withdrawal is 5M GP.'; }
    elseif ($amount > $bal)         { $error = 'Insufficient balance.'; }
    elseif (!$rsn)                   { $error = 'Please enter your RSN.'; }
    else {
        db_exec("UPDATE users SET {$col}={$col}-? WHERE id=?", 'ii', $amount, $user['id']);
        db_insert('INSERT INTO withdrawals (user_id, game, amount, rsn, trade_method) VALUES (?,?,?,?,?)',
            'iisss', $user['id'], $game, $amount, $rsn, $method);
        discord_send("📤 **Withdrawal Request**\n👤 {$user['username']}\n🪙 ".fmt_gp($amount)." {$game}\nRSN: {$rsn}\nMethod: {$method}");
        $success = true;
        $user = db_one('SELECT * FROM users WHERE id=?', 'i', $user['id']); // refresh
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="section">
  <div class="container">
    <div class="page-hero" style="padding:0 0 32px"><h1>📤 Withdraw GP</h1><p>Request a GP withdrawal to your RuneScape account.</p></div>
    <?php if ($success): ?>
    <div class="success-box"><div class="success-icon">✅</div><p class="text-gold" style="font-size:18px;font-weight:700">Withdrawal requested!</p><p class="text-muted mt-8">Our team will trade you the GP within 5–15 minutes. Open live chat if you need immediate assistance.</p><button class="btn-gold mt-24" data-open-chat>💬 Open Live Chat</button></div>
    <?php else: ?>
    <div class="form-wrap" style="max-width:480px">
      <div class="form-title">📤 Withdraw GP</div>
      <div style="display:flex;gap:12px;margin-bottom:20px">
        <div class="card" style="flex:1;text-align:center;padding:12px"><div style="font-size:11px;color:var(--text-muted)">OSRS Balance</div><div style="color:var(--gold);font-weight:700;font-size:16px"><?= fmt_gp((int)$user['balance_osrs']) ?></div></div>
        <div class="card" style="flex:1;text-align:center;padding:12px"><div style="font-size:11px;color:var(--text-muted)">RS3 Balance</div><div style="color:var(--gold);font-weight:700;font-size:16px"><?= fmt_gp((int)$user['balance_rs3']) ?></div></div>
      </div>
      <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group"><label>Game</label><select name="game"><option value="osrs">OSRS Gold</option><option value="rs3">RS3 Gold</option></select></div>
        <div class="form-group"><label>Amount (M GP) *</label><input type="number" name="amount" min="5" max="<?= max((int)$user['balance_osrs'],(int)$user['balance_rs3']) ?>" required placeholder="e.g. 100"></div>
        <div class="form-group"><label>Your RSN *</label><input type="text" name="rsn" maxlength="50" required placeholder="In-game name"></div>
        <div class="form-group"><label>Trade Method</label><select name="trade_method"><option value="face_to_face">Face to Face</option><option value="grand_exchange">Grand Exchange</option></select></div>
        <button type="submit" class="btn-primary btn-full">📤 Request Withdrawal</button>
        <p class="ssl-note">Processing time: 5–15 minutes · 24/7 support available</p>
      </form>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
