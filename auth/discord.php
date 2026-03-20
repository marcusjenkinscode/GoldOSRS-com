<?php
// auth/discord.php — Discord OAuth stub
// To fully implement: set DISCORD_CLIENT_ID, DISCORD_CLIENT_SECRET,
// DISCORD_REDIRECT_URI in config.php and use the Discord OAuth 2.0 flow.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
if (is_logged_in()) redirect('/dashboard.php');

$page_title = 'Discord Login | GoldOSRS';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-content">
<section class="section" style="min-height:60vh;display:flex;align-items:center">
  <div class="container">
    <div class="form-wrap" style="max-width:420px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">🎮</div>
      <h2 class="text-gold" style="font-family:'Cinzel Decorative',serif;font-size:20px;margin-bottom:12px">Discord Login</h2>
      <p class="text-muted" style="font-size:14px;margin-bottom:24px">Discord OAuth integration is coming soon. Please use your username &amp; password or join our Discord server for support.</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <a href="/login.php" class="btn-primary">⚔️ Login with Password</a>
        <a href="https://discord.gg/n9HP7GH2e3" class="btn-secondary" target="_blank" rel="noopener">Join Discord →</a>
      </div>
      <div class="mt-16"><a href="/register.php" class="text-gold" style="font-size:13px">Create an account →</a></div>
    </div>
  </div>
</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
