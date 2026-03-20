<?php
// auth/google.php — Google OAuth stub
// To fully implement: integrate Google OAuth 2.0 client library,
// set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in config.php.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
if (is_logged_in()) redirect('/dashboard.php');

$page_title = 'Google Login | GoldOSRS';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<section class="section" style="min-height:60vh;display:flex;align-items:center">
  <div class="container">
    <div class="form-wrap" style="max-width:420px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">🔐</div>
      <h2 class="text-gold" style="font-family:'Cinzel Decorative',serif;font-size:20px;margin-bottom:12px">Google Login</h2>
      <p class="text-muted" style="font-size:14px;margin-bottom:24px">Google OAuth integration is coming soon. Please use your username &amp; password in the meantime.</p>
      <a href="/login.php" class="btn-primary">⚔️ Login with Password</a>
      <div class="mt-16"><a href="/register.php" class="text-gold" style="font-size:13px">Create an account →</a></div>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
