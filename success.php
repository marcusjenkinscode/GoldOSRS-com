<?php
/**
 * GoldOSRS.com – Order Success Page
 */
$page_title = 'Order Confirmed';
require_once __DIR__ . '/includes/header.php';
?>

<div class="success-page container">
    <span class="success-icon">✅</span>
    <h1 class="gold-text">Order Confirmed!</h1>
    <p>Thank you for your purchase. Our team will process your order shortly.</p>
    <p style="margin-top:.5rem;">You'll receive a confirmation via email. For support, contact
        <a href="mailto:support@goldosrs.com">support@goldosrs.com</a> or join our Discord.
    </p>
    <div style="margin-top:2rem; display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <a href="/" class="btn btn-gold">Return Home</a>
        <a href="/order.php" class="btn btn-outline">Place Another Order</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
