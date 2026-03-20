<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$user = require_login();

$page_title = 'Deposit | GoldOSRS';
$page_desc  = 'Deposit GP or cryptocurrency to your GoldOSRS account.';

$order_id  = (int)get('order_id');
$order_ref = get('ref');
$order     = $order_id ? db_one('SELECT * FROM orders WHERE id=? AND (user_id=? OR guest_email=?)', 'iis', $order_id, $user['id'], $user['email']) : null;

$btc_address = '';
$btc_amount  = 0;
$deposit_id  = 0;

if ($order) {
    // Generate BTC address for this order
    $existing = db_one('SELECT * FROM deposits WHERE user_id=? AND status="pending" ORDER BY id DESC LIMIT 1', 'i', $user['id']);
    if ($existing && (time() - strtotime($existing['created_at'])) < 3600) {
        $btc_address = $existing['address'];
        $btc_amount  = $existing['amount_crypto'];
        $deposit_id  = $existing['id'];
    } else {
        $btc_address = get_new_btc_address();
        $btc_amount  = usd_to_btc((float)$order['price_usd']);
        $deposit_id  = db_insert(
            'INSERT INTO deposits (user_id, currency, address, amount_crypto, amount_usd, status) VALUES (?,?,?,?,?,?)',
            'issdds', $user['id'], 'BTC', $btc_address, $btc_amount, $order['price_usd'], 'pending'
        );
    }
    // Update order with BTC address
    db_exec('UPDATE orders SET btc_address=?, btc_amount=? WHERE id=?', 'sdi', $btc_address, $btc_amount, $order_id);
}

// Check payment status via AJAX
$check_payment = false;
if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    $dep = db_one('SELECT status FROM deposits WHERE id=? AND user_id=?', 'ii', (int)$_GET['dep'], $user['id']);
    echo json_encode(['status' => $dep['status'] ?? 'pending']);
    exit;
}

// QR code via Google Chart API
$qr_url = $btc_address ? 'https://chart.googleapis.com/chart?cht=qr&chs=180x180&chl=bitcoin:' . urlencode($btc_address) . '%3Famount%3D' . $btc_amount . '&choe=UTF-8' : '';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="section">
  <div class="container">
    <div class="page-hero" style="padding-top:0;padding-bottom:32px">
      <h1>💰 Deposit</h1>
      <p>Deposit GP to your account balance or pay for an order.</p>
    </div>

    <?php if ($order && $btc_address): ?>
    <!-- BTC payment for specific order -->
    <div class="btc-box" id="paymentBox">
      <h2 class="text-gold mb-16">Pay for Order <?= h($order_ref ?: 'GOS-' . str_pad($order_id,6,'0',STR_PAD_LEFT)) ?></h2>
      <div style="font-size:28px;font-weight:700;color:var(--gold);margin-bottom:4px">$<?= number_format((float)$order['price_usd'], 2) ?></div>
      <div class="text-muted mb-16" style="font-size:13px"><?= h($order['service_type']) ?></div>

      <?php if ($qr_url): ?>
      <img src="<?= h($qr_url) ?>" alt="BTC QR Code" class="btc-qr">
      <?php endif; ?>

      <p class="text-muted" style="font-size:12px;margin-bottom:6px">Send exactly:</p>
      <div style="font-size:24px;font-weight:700;color:var(--amber)">₿ <?= number_format($btc_amount, 8) ?></div>
      <p class="text-muted" style="font-size:11px;margin-bottom:12px">(<?= $btc_amount > 0 ? number_format($btc_amount, 8) : '0' ?> BTC)</p>

      <p class="text-muted" style="font-size:12px;margin-bottom:6px">To address:</p>
      <div class="btc-address" data-copy="<?= h($btc_address) ?>"><?= h($btc_address) ?></div>
      <p style="font-size:11px;color:var(--text-muted)">Click address to copy ↑</p>

      <div style="margin-top:20px">
        <div class="btc-timer" id="countdown">60:00</div>
        <div style="font-size:11px;color:var(--text-muted)">Time remaining</div>
      </div>

      <div class="payment-status payment-pending" id="payStatus">
        ⏳ Awaiting payment… (checking every 10 seconds)
      </div>
    </div>

    <script>
    // Countdown timer
    let secs = 3600;
    const timer = document.getElementById('countdown');
    const interval = setInterval(() => {
      secs--;
      const m = Math.floor(secs/60), s = secs%60;
      timer.textContent = m + ':' + String(s).padStart(2,'0');
      if (secs <= 0) { clearInterval(interval); timer.textContent = 'EXPIRED'; }
    }, 1000);

    // Poll payment status
    async function checkPayment() {
      try {
        const r = await fetch('/deposit.php?check=1&dep=<?= $deposit_id ?>&order_id=<?= $order_id ?>');
        const d = await r.json();
        if (d.status === 'confirmed' || d.status === 'credited') {
          document.getElementById('payStatus').className = 'payment-status payment-confirmed';
          document.getElementById('payStatus').textContent = '✅ Payment confirmed! Redirecting…';
          clearInterval(pollInterval);
          setTimeout(() => window.location = '/dashboard.php', 2000);
        }
      } catch(e) {}
    }
    const pollInterval = setInterval(checkPayment, 10000);
    </script>

    <?php else: ?>
    <!-- General deposit options -->
    <div class="grid-3">
      <div class="card text-center">
        <div class="card-icon">₿</div>
        <h3>Bitcoin (BTC)</h3>
        <p>Send BTC to get OSRS/RS3 GP credited to your account. Rates update daily.</p>
        <p class="text-muted" style="font-size:12px">1 USD ≈ <?= number_format(GP_PER_USD, 1) ?>M OSRS GP</p>
        <button class="btn-gold mt-16" onclick="requestDepositAddress('BTC')">Generate Address</button>
      </div>
      <div class="card text-center">
        <div class="card-icon">⚔️</div>
        <h3>OSRS Gold Trade</h3>
        <p>Trade OSRS gold to one of our accounts in-game. Our team will credit your balance.</p>
        <button class="btn-gold mt-16" data-open-chat>Contact Support</button>
      </div>
      <div class="card text-center">
        <div class="card-icon">🎮</div>
        <h3>RS3 Gold Trade</h3>
        <p>Trade RS3 gold to one of our accounts in-game. Contact support to arrange.</p>
        <button class="btn-gold mt-16" data-open-chat>Contact Support</button>
      </div>
    </div>

    <div id="depositAddressBox" style="display:none;opacity:0" class="btc-box mt-32">
      <h3 class="text-gold mb-16">Your BTC Deposit Address</h3>
      <img id="depQR" alt="QR Code" class="btc-qr">
      <div class="btc-address btc-address-reveal" id="depAddress" data-copy="">Loading…</div>
      <p style="font-size:11px;color:var(--text-muted)">Click address to copy · Tap &amp; hold on mobile</p>
      <p class="text-muted mt-16" style="font-size:12px">GP will be credited within ~10 minutes of 1 confirmation.</p>
    </div>

    <script>
    async function requestDepositAddress(currency) {
      const box = document.getElementById('depositAddressBox');
      const btn = document.querySelector('[onclick*="requestDepositAddress"]');
      if (btn) { btn.disabled = true; btn.textContent = '⏳ Generating…'; }
      const r = await fetch('/api/check_payment.php?action=gen_address&currency=' + currency, {
        method:'POST',
        body: new URLSearchParams({ csrf: '<?= h(csrf_token()) ?>', currency })
      });
      const d = await r.json();
      if (btn) { btn.disabled = false; btn.textContent = 'Generate Address'; }
      if (d.address) {
        const addrEl = document.getElementById('depAddress');
        addrEl.textContent = d.address;
        addrEl.dataset.copy = d.address;
        addrEl.classList.remove('btc-address-reveal');
        void addrEl.offsetWidth; // force reflow to restart animation
        addrEl.classList.add('btc-address-reveal');
        document.getElementById('depQR').src = 'https://chart.googleapis.com/chart?cht=qr&chs=180x180&chl=' + encodeURIComponent(d.address) + '&choe=UTF-8';
        // Smooth reveal of the box
        box.style.display = '';
        requestAnimationFrame(() => {
          box.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
          box.style.transform  = 'translateY(-10px)';
          box.style.opacity    = '0';
          requestAnimationFrame(() => {
            box.style.transform = 'translateY(0)';
            box.style.opacity   = '1';
          });
        });
        // Re-init copy button
        if (typeof initCopyBtns === 'function') initCopyBtns();
        // Scroll to it on mobile
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        alert(d.error || 'Could not generate address. Please contact support.');
      }
    }
    </script>
    <?php endif; ?>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
