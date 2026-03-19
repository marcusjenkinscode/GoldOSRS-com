<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'Privacy Policy | GoldOSRS';
$page_desc  = 'GoldOSRS Privacy Policy — how we handle your data.';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero"><h1>Privacy Policy</h1><p>Last updated: January 2026</p></section>
<section class="section" style="padding-top:20px">
  <div class="container" style="max-width:800px">
    <div class="card" style="line-height:1.9;font-size:14px;color:var(--text-muted)">
      <h3 class="text-gold mb-16">Data We Collect</h3>
      <p>We collect: username, email address, IP address (for security), order details, and game history. We do not sell your data to third parties.</p>
      <h3 class="text-gold mt-24 mb-16">How We Use Your Data</h3>
      <p>Your data is used to process orders, provide customer support, prevent fraud, and improve our services. Email is used for order confirmations and important account updates.</p>
      <h3 class="text-gold mt-24 mb-16">Cookies</h3>
      <p>We use a session cookie to keep you logged in. No advertising or tracking cookies are used.</p>
      <h3 class="text-gold mt-24 mb-16">Data Retention</h3>
      <p>Order records are retained for 12 months. You may request deletion of your account by contacting support via live chat.</p>
      <h3 class="text-gold mt-24 mb-16">Security</h3>
      <p>Passwords are hashed with bcrypt. All connections use SSL/TLS. We never store payment card details — payments are processed by third-party providers.</p>
      <h3 class="text-gold mt-24 mb-16">Contact</h3>
      <p>For privacy enquiries, contact us via live chat or email: <a href="mailto:support@goldosrs.com" class="text-gold">support@goldosrs.com</a></p>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
