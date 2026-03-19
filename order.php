<?php
/**
 * GoldOSRS.com – Order Form (Step 2)
 * Reads optional GET params to pre-fill the form (Step 6).
 * On POST, adds item to session basket and redirects to payment.
 */

require_once __DIR__ . '/includes/functions.php';
start_session();

// --------------------------------------------------------------------------
// Available services catalogue
// --------------------------------------------------------------------------
$services = [
    'osrs-gold'        => ['label' => 'OSRS Gold',         'base_price' => 3.99],
    'rs3-gold'         => ['label' => 'RS3 Gold',          'base_price' => 1.99],
    'quest-completion' => ['label' => 'Quest Completion',  'base_price' => 9.99],
    'skill-training'   => ['label' => 'Skill Training',    'base_price' => 14.99],
    'account-leveling' => ['label' => 'Account Leveling',  'base_price' => 24.99],
    'minigame-service' => ['label' => 'Minigame Service',  'base_price' => 12.99],
];

// --------------------------------------------------------------------------
// Step 6: Pre-fill from GET params (sanitise before output)
// --------------------------------------------------------------------------
$prefill_service = array_key_exists($_GET['service'] ?? '', $services)
    ? $_GET['service']
    : '';

$prefill_price = isset($_GET['price'])
    ? max(0.01, (float)$_GET['price'])
    : ($prefill_service ? $services[$prefill_service]['base_price'] : '');

// --------------------------------------------------------------------------
// POST: validate and add to basket
// --------------------------------------------------------------------------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $service = $_POST['service'] ?? '';
    $amount  = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment = $_POST['payment_method'] ?? '';

    if (!array_key_exists($service, $services)) {
        $errors[] = 'Please select a valid service.';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0.';
    }
    if (!in_array($payment, ['bitcoin', 'card'], true)) {
        $errors[] = 'Please choose a payment method.';
    }

    if (empty($errors)) {
        basket_add($service, $amount, $payment);
        flash_set('success', 'Service added to basket!');
        header('Location: /payment.php');
        exit;
    }
}

$page_title = 'Order Services';
require_once __DIR__ . '/includes/header.php';
?>

<div class="order-page">
    <h1 class="gold-text text-center" style="margin-bottom:2rem;">Place Your Order</h1>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error" style="margin-bottom:1.5rem;">
        <?= h(implode(' ', $errors)) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="/order.php" class="form-section" style="max-width:100%;">
        <?= csrf_field() ?>

        <!-- Service selector -->
        <div class="form-group">
            <label>Select Service</label>
            <div class="service-selector" id="serviceSelector">
                <?php foreach ($services as $key => $svc): ?>
                <div class="service-option <?= ($prefill_service === $key) ? 'selected' : '' ?>"
                     data-value="<?= h($key) ?>"
                     data-price="<?= h((string)$svc['base_price']) ?>"
                     onclick="selectService(this)">
                    <div class="service-name"><?= h($svc['label']) ?></div>
                    <div class="service-price">From $<?= number_format($svc['base_price'], 2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="service" id="serviceInput"
                   value="<?= h($prefill_service) ?>" required>
        </div>

        <!-- Amount -->
        <div class="form-group">
            <label for="amount">Amount (USD)</label>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                   value="<?= h((string)$prefill_price) ?>"
                   placeholder="e.g. 9.99" required>
            <p class="form-hint">Total in US dollars.</p>
        </div>

        <!-- Notes -->
        <div class="form-group">
            <label for="notes">Order Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Your RSN, world number, special instructions…"></textarea>
        </div>

        <!-- Payment method -->
        <div class="form-group">
            <label>Payment Method</label>
            <div class="pay-tabs" id="payTabs">
                <button type="button" class="pay-tab active" data-method="bitcoin"
                        onclick="selectPay('bitcoin', this)">
                    ₿ Bitcoin
                </button>
                <button type="button" class="pay-tab" data-method="card"
                        onclick="selectPay('card', this)">
                    💳 Card
                </button>
            </div>
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="bitcoin">
        </div>

        <button type="submit" class="btn btn-gold btn-lg btn-block">
            Add to Basket &amp; Continue to Payment →
        </button>
    </form>
</div>

<script>
function selectService(el) {
    document.querySelectorAll('.service-option').forEach(function(o){ o.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('serviceInput').value = el.dataset.value;
    // Auto-fill price if user hasn't overridden
    var amtField = document.getElementById('amount');
    if (!amtField.dataset.manuallySet) {
        amtField.value = parseFloat(el.dataset.price).toFixed(2);
    }
}

document.getElementById('amount').addEventListener('input', function(){
    this.dataset.manuallySet = 'true';
});

function selectPay(method, btn) {
    document.querySelectorAll('.pay-tab').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('paymentMethodInput').value = method;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
