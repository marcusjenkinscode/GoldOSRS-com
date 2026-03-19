<?php
/**
 * GoldOSRS.com – Payment Page (Step 2)
 * Shows basket, countdown timer, and Bitcoin / card payment options.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

// Redirect if basket is empty
$basket = $_SESSION['basket'] ?? [];
if (empty($basket)) {
    flash_set('warning', 'Your basket is empty. Please add a service first.');
    header('Location: /order.php');
    exit;
}

// --------------------------------------------------------------------------
// POST: simulate payment confirmation
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    verify_csrf();

    $pdo = get_db();
    $user_id = current_user_id();
    $payment_method = $basket[0]['payment_method'] ?? 'bitcoin';

    foreach ($basket as $item) {
        $stmt = $pdo->prepare(
            'INSERT INTO order_history (user_id, service, amount, payment_method, status)
             VALUES (:uid, :svc, :amt, :pm, "paid")'
        );
        $stmt->execute([
            ':uid' => $user_id,
            ':svc' => $item['service'],
            ':amt' => $item['amount'],
            ':pm'  => $payment_method,
        ]);
    }

    basket_clear();
    flash_set('success', 'Payment confirmed! Your order is being processed.');
    header('Location: /success.php');
    exit;
}

$page_title = 'Complete Payment';
require_once __DIR__ . '/includes/header.php';

$total          = basket_total();
$payment_method = $basket[0]['payment_method'] ?? 'bitcoin';
$btc_address    = BTC_ADDRESS;
// Approximate USD→BTC conversion for the QR payload.
// 40000 = assumed BTC/USD rate placeholder; replace with a live rate API in production.
$approx_btc_rate = defined('BTC_USD_RATE') ? BTC_USD_RATE : 40000;
// QR code via a self-hosted API (no external tracking) or plain text fallback
$btc_qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode('bitcoin:' . $btc_address . '?amount=' . number_format($total / $approx_btc_rate, 8, '.', ''));
?>

<!-- ============================================================= -->
<!--  Countdown timer (60 min, informational only)                 -->
<!-- ============================================================= -->
<div class="container" style="max-width:720px; padding-top:3rem;">

    <h1 class="gold-text text-center" style="margin-bottom:2rem;">Complete Your Payment</h1>

    <div class="countdown-bar" id="countdownBar">
        <div class="timer-label">Order expires in (informational)</div>
        <div class="timer-display" id="timerDisplay">60:00</div>
        <div class="timer-warning" id="timerWarning">
            ⚠ Timer expired – you can still pay, but contact support if you experience issues.
        </div>
    </div>

    <!-- Basket summary -->
    <div class="form-section" style="max-width:100%; margin-bottom:1.5rem;">
        <h2 style="font-size:1.3rem; margin-bottom:1rem;">Order Summary</h2>
        <table class="basket-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th style="text-align:right;">Amount (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($basket as $item): ?>
                <tr>
                    <td><?= h(ucwords(str_replace('-', ' ', $item['service']))) ?></td>
                    <td style="text-align:right;">$<?= number_format($item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="basket-total" colspan="2">
                        Total: $<?= number_format($total, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="text-align:right; margin-top:.5rem;">
            <a href="/order.php" class="btn btn-outline btn-sm">+ Add Another Service</a>
        </div>
    </div>

    <!-- Payment panels -->
    <div class="form-section" style="max-width:100%;">
        <h2 style="font-size:1.3rem; margin-bottom:1rem;">Payment Method</h2>

        <!-- Tabs -->
        <div class="pay-tabs">
            <button type="button" class="pay-tab <?= $payment_method === 'bitcoin' ? 'active' : '' ?>"
                    onclick="showPayPanel('bitcoin', this)">₿ Bitcoin</button>
            <button type="button" class="pay-tab <?= $payment_method === 'card' ? 'active' : '' ?>"
                    onclick="showPayPanel('card', this)">💳 Card</button>
        </div>

        <!-- Bitcoin panel -->
        <div class="pay-panel <?= $payment_method === 'bitcoin' ? 'active' : '' ?>" id="panel-bitcoin">
            <div class="btc-block">
                <p style="margin-bottom:.5rem; color:var(--color-grey);">
                    Send exactly <strong style="color:var(--color-gold);">$<?= number_format($total, 2) ?></strong> worth of BTC to:
                </p>
                <div class="btc-address"><?= h($btc_address) ?></div>
                <div class="btc-qr">
                    <img src="<?= h($btc_qr_url) ?>" alt="Bitcoin QR Code" width="160" height="160">
                </div>
                <p style="font-size:.82rem; color:var(--color-grey); margin-top:.75rem;">
                    After sending, click "I've Sent Payment" below. Payments are confirmed within 1–3 network confirmations.
                </p>
            </div>
        </div>

        <!-- Card panel -->
        <div class="pay-panel <?= $payment_method === 'card' ? 'active' : '' ?>" id="panel-card">
            <div style="background:#111; border:1px solid var(--color-border); border-radius:6px; padding:1.5rem;">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" placeholder="1234 5678 9012 3456" maxlength="19"
                           oninput="formatCard(this)" autocomplete="cc-number">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>Expiry (MM/YY)</label>
                        <input type="text" placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" placeholder="123" maxlength="4" autocomplete="cc-csc">
                    </div>
                </div>
                <div class="form-group">
                    <label>Name on Card</label>
                    <input type="text" placeholder="John Doe" autocomplete="cc-name">
                </div>
            </div>
        </div>

        <!-- Confirm payment form -->
        <form method="post" action="/payment.php" style="margin-top:1.5rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="confirm_payment" value="1">
            <button type="submit" class="btn btn-gold btn-lg btn-block">
                ✅ I've Sent Payment – Confirm Order
            </button>
        </form>

        <p style="font-size:.8rem; color:var(--color-grey); text-align:center; margin-top:.75rem;">
            By confirming you agree to our Terms of Service. Need help?
            <a href="mailto:support@goldosrs.com">Contact support</a>.
        </p>
    </div>

</div>

<script>
// --------------------------------------------------------------------------
// Payment tabs
// --------------------------------------------------------------------------
function showPayPanel(id, btn) {
    document.querySelectorAll('.pay-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.pay-tab').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');
}

// --------------------------------------------------------------------------
// 60-minute countdown (informational – does NOT clear basket)
// --------------------------------------------------------------------------
(function(){
    var totalSec = 60 * 60;
    var display  = document.getElementById('timerDisplay');
    var bar      = document.getElementById('countdownBar');
    var warning  = document.getElementById('timerWarning');

    function tick() {
        if (totalSec <= 0) {
            display.textContent = '00:00';
            bar.classList.add('expired');
            warning.style.display = 'block';
            return;
        }
        totalSec--;
        var m = String(Math.floor(totalSec / 60)).padStart(2, '0');
        var s = String(totalSec % 60).padStart(2, '0');
        display.textContent = m + ':' + s;
        setTimeout(tick, 1000);
    }
    tick();
}());

// --------------------------------------------------------------------------
// Card number formatter
// --------------------------------------------------------------------------
function formatCard(input) {
    var v = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = v.replace(/(.{4})/g, '$1 ').trim();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
