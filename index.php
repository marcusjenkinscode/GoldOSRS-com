<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();

$page_title = 'Buy OSRS Gold | Cheap Old School RuneScape Gold & RS3 GP | GoldOSRS';
$page_desc  = 'Buy cheap OSRS gold, RS3 gold, Inferno Cape, questing and power levelling. Fast 5-minute delivery, secure payments. Trusted since 2018 — 4.9★ Trustpilot.';
$page_keywords = 'buy osrs gold, cheap osrs gold, rs3 gold, buy runescape gold, inferno cape service, osrs services, runescape gold seller';

$prices = get_prices();

// Handle order form submission
$order_success = false;
$order_ref = '';
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    csrf_check();
    if (!rate_limit('order', 5)) { $form_error = 'Please wait before submitting again.'; }
    else {
        $service_type   = post('service_type');
        $rsn            = post('rsn');
        $email          = post('email');
        $amount         = (int)post('amount', '0');
        $payment_method = post('payment_method', 'crypto');
        $trade_method   = post('trade_method', 'face_to_face');
        $details        = post('details');
        $discord        = post('discord');
        $promo          = post('promo');

        if (!$service_type || !$rsn || !$email) {
            $form_error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'Please enter a valid email address.';
        } else {
            $user_id = is_logged_in() ? (int)$_SESSION['user_id'] : null;

            // Determine order type
            $type = 'service';
            if (str_contains($service_type, 'Gold') || str_contains($service_type, 'gold')) $type = 'buy';
            elseif (str_contains($service_type, 'Swap') || str_contains($service_type, 'swap')) $type = 'swap';

            $game = str_contains(strtolower($service_type), 'rs3') ? 'rs3' : 'osrs';

            // Price calculation
            $price_key = ($game === 'rs3') ? 'rs3_' . $payment_method : 'osrs_' . $payment_method;
            $rate = get_price($price_key, 0.26);
            $price_usd = ($amount > 0) ? round($amount * $rate, 2) : 0;

            // Insert order
            $order_id = db_insert(
                'INSERT INTO orders (user_id, guest_email, guest_rsn, type, service_type, game, amount, price_usd, payment_method, rsn, trade_method, details, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                'isssssidsssss',
                $user_id, $email, $rsn, $type, $service_type, $game, $amount, $price_usd, $payment_method, $rsn, $trade_method, $details, 'pending'
            );

            if ($order_id) {
                $order_ref = 'GOS-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
                // Email confirmation
                email_order_received($email, $rsn, $service_type, $order_ref);
                // Discord notify
                discord_send("🛒 **New Order #{$order_ref}**\n⚔️ Service: {$service_type}\n👤 RSN: {$rsn}\n💰 Amount: " . ($amount > 0 ? fmt_gp($amount) : 'N/A') . "\n💵 USD: \${$price_usd}\n📧 Email: {$email}\n💳 Payment: {$payment_method}");
                // Real toast
                db_insert("INSERT INTO toasts (type, content) VALUES ('real', ?)", 's', "🪙 Someone just ordered {$service_type} — {$rsn}");
                $order_success = true;
                // If crypto payment, redirect to payment page
                if ($payment_method === 'crypto' && $price_usd > 0) {
                    header("Location: /deposit.php?order_id={$order_id}&ref={$order_ref}");
                    exit;
                }
            } else {
                $form_error = 'Something went wrong. Please try again or contact support.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero-badge">⚔ Trusted Since 2018 · 4.9★ Trustpilot ⚔</div>
    <h1>Gold OSRS<span>The Realm's Finest Marketplace</span></h1>
    <p class="hero-sub">OSRS &amp; RS3 gold, Inferno Cape, questing, power levelling, boss services, accounts and more — delivered fast by veteran adventurers.</p>
    <div class="hero-stats">
      <div class="hero-stat"><strong data-count="182400">0</strong><span>Orders Fulfilled</span></div>
      <div class="hero-stat"><strong>4.9★</strong><span>Trustpilot Rating</span></div>
      <div class="hero-stat"><strong>5 Min</strong><span>Avg. Delivery</span></div>
      <div class="hero-stat"><strong>24/7</strong><span>Live Support</span></div>
    </div>
    <div class="hero-ctas">
      <a href="#order" class="btn-primary">⚔️ Order Now</a>
      <a href="/services.php" class="btn-secondary">View Services</a>
    </div>
  </div>
</section>

<!-- Feats -->
<div class="feats-bar">
  <div class="feat"><div class="feat-icon">⚡</div><h4>Lightning Delivery</h4><p>Gold orders in under 5 minutes</p></div>
  <div class="feat"><div class="feat-icon">🛡️</div><h4>100% Safe</h4><p>VPN + undetectable methods</p></div>
  <div class="feat"><div class="feat-icon">🔒</div><h4>Secure Payment</h4><p>Crypto &amp; card encrypted checkout</p></div>
  <div class="feat"><div class="feat-icon">🏆</div><h4>Refund Guarantee</h4><p>Full refund if order fails</p></div>
  <div class="feat"><div class="feat-icon">💬</div><h4>24/7 Support</h4><p>Real humans, not bots</p></div>
</div>

<!-- Prices -->
<section class="section prices-section">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Live Market Rates</span>
      <h2 class="section-title">Current Gold Prices</h2>
      <p class="section-sub">Real-time rates. Crypto orders get 5% discount.</p>
    </div>
    <div class="tabs">
      <button class="tab-btn active" data-tab="osrs">OSRS Gold</button>
      <button class="tab-btn" data-tab="rs3">RS3 Gold</button>
      <button class="tab-btn" data-tab="swap">Swap Rates</button>
    </div>
    <div class="tabs-wrap">
      <div class="tab-panel active grid-3" data-tab="osrs">
        <div class="card"><div class="card-badge">Popular</div><div class="card-icon">🪙</div><h3>OSRS Gold — Crypto</h3><p class="card-game">Old School RuneScape</p><span class="price-amount">$<?= number_format($prices['osrs_crypto'] ?? 0.26, 2) ?> per Million GP</span><p class="price-label">Live rate · Crypto 5% discount</p><a href="#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-icon">💳</div><h3>OSRS Gold — Card</h3><p class="card-game">Old School RuneScape</p><span class="price-amount">$<?= number_format($prices['osrs_card'] ?? 0.29, 2) ?> per Million GP</span><p class="price-label">Live rate · Visa, Mastercard, PayPal</p><a href="#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-badge">Best Value</div><div class="card-icon">⚡</div><h3>Bulk OSRS (1B+)</h3><p class="card-game">Old School RuneScape</p><span class="price-amount">$<?= number_format($prices['osrs_bulk'] ?? 0.24, 2) ?> per Million GP</span><p class="price-label">Contact for bulk rates</p><a href="#order" class="btn-gold mt-16">Contact Us</a></div>
      </div>
      <div class="tab-panel grid-3" data-tab="rs3">
        <div class="card"><div class="card-badge">Popular</div><div class="card-icon">🪙</div><h3>RS3 Gold — Crypto</h3><p class="card-game">RuneScape 3</p><span class="price-amount">$<?= number_format($prices['rs3_crypto'] ?? 0.05, 2) ?> per Million GP</span><p class="price-label">Live rate · Crypto 5% discount</p><a href="#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-icon">💳</div><h3>RS3 Gold — Card</h3><p class="card-game">RuneScape 3</p><span class="price-amount">$<?= number_format($prices['rs3_card'] ?? 0.06, 2) ?> per Million GP</span><p class="price-label">Live rate</p><a href="#order" class="btn-gold mt-16">Order Now</a></div>
        <div class="card"><div class="card-badge">Best Value</div><div class="card-icon">⚡</div><h3>Bulk RS3 (5B+)</h3><p class="card-game">RuneScape 3</p><span class="price-amount">$<?= number_format($prices['rs3_bulk'] ?? 0.04, 2) ?> per Million GP</span><p class="price-label">Contact for bulk rates</p><a href="#order" class="btn-gold mt-16">Contact Us</a></div>
      </div>
      <div class="tab-panel grid-3" data-tab="swap">
        <div class="card"><div class="card-icon">🔄</div><h3>OSRS → RS3</h3><p class="card-game">Gold Swap</p><span class="price-amount">1M OSRS ≈ <?= number_format($prices['swap_rate'] ?? 5.8, 1) ?>M RS3</span><p class="price-label">Live rate · Instant swap</p><a href="#order" class="btn-gold mt-16">Swap Now</a></div>
        <div class="card"><div class="card-icon">🔄</div><h3>RS3 → OSRS</h3><p class="card-game">Gold Swap</p><span class="price-amount"><?= number_format($prices['swap_rate'] ?? 5.8, 1) ?>M RS3 ≈ 1M OSRS</span><p class="price-label">Live rate · Instant swap</p><a href="#order" class="btn-gold mt-16">Swap Now</a></div>
        <div class="card"><div class="card-icon">💬</div><h3>Bulk Swap</h3><p class="card-game">Both Games</p><span class="price-amount">Custom Rates</span><p class="price-label">Chat for bulk deals</p><a href="#" data-open-chat class="btn-gold mt-16">Contact Us</a></div>
      </div>
    </div>
  </div>
</section>

<!-- Services -->
<section class="section" style="background:rgba(255,215,0,0.01)">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Skilled Adventurers for Hire</span>
      <h2 class="section-title">Our Services</h2>
      <p class="section-sub">Every service completed by veteran players with thousands of hours.</p>
    </div>
    <div class="grid-3">
      <div class="card"><div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000;border-color:rgba(255,80,0,0.3)">🔥 OSRS</div><div class="card-icon">🔥</div><h3>Inferno Cape</h3><p>100% completion guarantee or full refund. Account safety prioritized.</p><ul><li>100% completion guarantee</li><li>Full refund if unsuccessful</li><li>Account safety prioritized</li></ul><a href="#order" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">⚔️</div><h3>Questing</h3><p>Any quest completed — novice to grandmaster. Quest capes available.</p><ul><li>All quests available</li><li>Quest cape completion</li><li>Fast turnaround</li></ul><a href="#order" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">📈</div><h3>Power Levelling</h3><p>Blazing-fast skill training. Combat, skilling, 99s, maxing.</p><ul><li>All skills covered</li><li>99s &amp; Max cape</li><li>XP rate guaranteed</li></ul><a href="#order" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">🏆</div><h3>Boss Services</h3><p>Raids, ToB, CoX, Nex, Zamorak — kills, KC boosts, unique farming.</p><ul><li>All raids available</li><li>Unique item farming</li><li>KC boost available</li></ul><a href="#order" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">🎯</div><h3>Minigame Boosts</h3><p>NMZ, Pest Control, Soul Wars, BA — any minigame reward or grind.</p><ul><li>All minigames</li><li>Points grinding</li><li>Reward unlocks</li></ul><a href="#order" class="btn-gold">Order Now</a></div>
      <div class="card"><div class="card-icon">👤</div><h3>Accounts for Sale</h3><p>Starters, pures, near-maxed, maxed mains. Verified &amp; secure.</p><ul><li>Starters &amp; pures</li><li>Near-maxed mains</li><li>Fully maxed builds</li></ul><a href="/accounts.php" class="btn-gold">Browse Accounts</a></div>
    </div>
    <div class="text-center mt-32"><a href="/services.php" class="btn-secondary">View All Services ⚔️</a></div>
  </div>
</section>

<!-- Order Form -->
<section class="section" id="order">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Place Your Order</span>
      <h2 class="section-title">Start Your Adventure</h2>
    </div>
    <?php if ($order_success): ?>
    <div class="success-box">
      <div class="success-icon">✅</div>
      <div class="success-ref"><?= h($order_ref) ?></div>
      <p>Order received! Check your email for confirmation.</p>
      <p class="mt-16">Open the live chat below so our team can begin your order immediately.</p>
      <button class="btn-gold mt-24" data-open-chat>💬 Open Live Chat</button>
    </div>
    <?php else: ?>
    <div class="form-wrap">
      <div class="form-title">⚔️ New Order</div>
      <?php if ($form_error): ?><div class="form-alert show"><?= h($form_error) ?></div><?php endif; ?>
      <form method="POST" action="#order" id="orderForm">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Service Type *</label>
          <select name="service_type" required>
            <option value="">— Select a service —</option>
            <optgroup label="Buy Gold"><option>OSRS Gold — Crypto</option><option>OSRS Gold — Card</option><option>RS3 Gold — Crypto</option><option>RS3 Gold — Card</option><option>Bulk OSRS Gold (1B+)</option><option>Bulk RS3 Gold (5B+)</option></optgroup>
            <optgroup label="Gold Swap"><option>Gold Swap — OSRS → RS3</option><option>Gold Swap — RS3 → OSRS</option><option>Bulk Gold Swap</option></optgroup>
            <optgroup label="Services"><option>Inferno Cape</option><option>Fire Cape</option><option>Questing — Single</option><option>Questing — Bundle</option><option>Quest Cape</option><option>Power Levelling — Combat</option><option>Power Levelling — Skill</option><option>Power Levelling — 99</option><option>Max Cape / Maxing</option><option>Boss Service — ToB</option><option>Boss Service — CoX</option><option>Boss Service — Nex</option><option>Boss Service — Other</option><option>Minigame — NMZ</option><option>Minigame — Pest Control</option><option>Minigame — Soul Wars</option><option>Minigame — BA</option></optgroup>
            <optgroup label="Accounts"><option>Buy Account — Starter</option><option>Buy Account — Pure</option><option>Buy Account — Near Maxed</option><option>Buy Account — Maxed</option></optgroup>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Amount (GP Millions)</label>
            <input type="number" name="amount" min="0" placeholder="e.g. 100">
          </div>
          <div class="form-group">
            <label>Trade Method</label>
            <select name="trade_method">
              <option value="face_to_face">Face to Face</option>
              <option value="grand_exchange">Grand Exchange</option>
              <option value="chest">Chest Trade</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Service Details *</label>
          <textarea name="details" placeholder="Any specific info, current level, preferences…" required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>In-Game Name (RSN) *</label>
            <input type="text" name="rsn" maxlength="50" required placeholder="Your RSN">
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="you@email.com" value="<?= h(current_user()['email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Discord (optional)</label>
            <input type="text" name="discord" maxlength="80" placeholder="yourname#0000">
          </div>
          <div class="form-group">
            <label>Promo Code</label>
            <input type="text" name="promo" maxlength="20" placeholder="PROMO20">
          </div>
        </div>
        <div class="form-group">
          <label>Payment Method *</label>
          <div class="payment-options">
            <label class="payment-opt selected">
              <input type="radio" name="payment_method" value="crypto" checked>
              <span class="payment-opt-icon">🪙</span>
              <span class="payment-opt-label">Cryptocurrency</span>
              <span class="payment-opt-sub">BTC, ETH, LTC · 5% discount</span>
            </label>
            <label class="payment-opt">
              <input type="radio" name="payment_method" value="card">
              <span class="payment-opt-icon">💳</span>
              <span class="payment-opt-label">Card / PayPal</span>
              <span class="payment-opt-sub">Visa, Mastercard, PayPal</span>
            </label>
          </div>
        </div>
        <div class="form-group">
          <label>Additional Notes</label>
          <textarea name="notes" placeholder="Anything else we should know…" rows="2"></textarea>
        </div>
        <button type="submit" name="place_order" class="btn-primary btn-full">⚔️ Place Order</button>
        <p class="ssl-note">🔒 SSL Encrypted · Your data is safe</p>
      </form>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Reviews -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Trustpilot Verified</span>
      <h2 class="section-title">What Adventurers Say</h2>
    </div>
    <div class="grid-3">
      <div class="review-card"><div class="review-stars">★★★★★</div><p class="review-text">"Got 500M GP in 3 minutes. Flawless trade!"</p><div class="review-author">DragonSlayer99</div><div class="review-service">OSRS Gold · 500M</div><div class="review-verified">✅ Verified Purchase</div></div>
      <div class="review-card"><div class="review-stars">★★★★★</div><p class="review-text">"Inferno done in under 3 hours! Super professional."</p><div class="review-author">Zulrah_Master</div><div class="review-service">Inferno Cape Service</div><div class="review-verified">✅ Verified Purchase</div></div>
      <div class="review-card"><div class="review-stars">★★★★★</div><p class="review-text">"Quest cape done in 2 days! Perfect support throughout."</p><div class="review-author">AzureLord7</div><div class="review-service">Quest Cape Service</div><div class="review-verified">✅ Verified Purchase</div></div>
    </div>
    <div class="trustpilot-block mt-32">
      <div class="trustpilot-stars">★★★★★</div>
      <div class="trustpilot-score">4.9 / 5</div>
      <div class="trustpilot-count mt-8">2,400+ Verified Reviews on Trustpilot</div>
      <a href="/reviews.php" class="btn-secondary mt-24">Read All Reviews ⭐</a>
    </div>
  </div>
</section>

<script>
// Pass prices to JS calculator
window.GOLD_PRICES = <?= json_encode($prices) ?>;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
