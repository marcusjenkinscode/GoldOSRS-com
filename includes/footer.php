<?php // includes/footer.php
$user = current_user();
?>
<!-- Footer -->
<footer class="footer">
  <div class="footer-container">
    <div class="footer-brand">
      <img src="/assets/images/logo.svg" alt="GoldOSRS" width="48" height="48">
      <div>
        <span class="footer-logo-text">Gold OSRS</span>
        <span class="footer-logo-sub">Realm's Marketplace</span>
      </div>
      <p>The realm's most trusted OSRS &amp; RS3 marketplace. Gold, services, accounts &amp; more — delivered by seasoned adventurers.</p>
    </div>
    <div class="footer-col">
      <h4>Services</h4>
      <ul>
        <li><a href="/buy-gold.php">Buy Gold</a></li>
        <li><a href="/sell-gold.php">Sell Gold</a></li>
        <li><a href="/services.php">All Services</a></li>
        <li><a href="/gambling.php">Gambling</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Information</h4>
      <ul>
        <li><a href="/reviews.php">Reviews</a></li>
        <li><a href="/faq.php">FAQ</a></li>
        <li><a href="/dashboard.php">Dashboard</a></li>
        <li><a href="/terms.php">Terms of Service</a></li>
        <li><a href="/privacy.php">Privacy Policy</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Connect</h4>
      <ul>
        <li><a href="https://discord.gg/n9HP7GH2e3" target="_blank" rel="noopener">Discord Server</a></li>
        <li><a href="#" id="footerChatBtn">Live Chat</a></li>
        <?php if ($user && $user['role'] === 'admin'): ?>
        <li><a href="/admin/">Admin Panel</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> GoldOSRS.com · All Rights Reserved.</p>
    <p>Not affiliated with Jagex Ltd. RuneScape® is a trademark of Jagex Ltd.</p>
  </div>
</footer>

<!-- ═══════════════════════════════════════════════════════════════════════════
     LIVE CHAT WIDGET
     ═══════════════════════════════════════════════════════════════════════ -->
<div id="chat-wrapper">
  <button class="chat-fab" id="chatFab" aria-label="Live Chat">
    <span class="chat-fab-icon">💬</span>
    <span class="chat-badge" id="chatBadge" style="display:none">1</span>
  </button>

  <div class="chat-window" id="chatWindow">
    <div class="chat-header">
      <div class="chat-header-info">
        <span class="chat-agent-avatar">⚔️</span>
        <div>
          <strong>Support Agent</strong>
          <span class="chat-status">● Online · Avg reply &lt;1 min</span>
        </div>
      </div>
      <button class="chat-close" id="chatClose" aria-label="Close chat">✕</button>
    </div>

    <?php if (!$user): ?>
    <!-- Guest: ask for name before chatting -->
    <div class="chat-guest-form" id="chatGuestForm">
      <p>Please enter your name to start chatting:</p>
      <input type="text" id="guestName" placeholder="Your name or RSN" maxlength="50">
      <input type="email" id="guestEmail" placeholder="Email (optional)" maxlength="255">
      <button id="startChatBtn" class="btn-gold">Start Chat ⚔️</button>
    </div>
    <?php endif; ?>

    <div class="chat-messages" id="chatMessages" style="<?= !$user ? 'display:none' : '' ?>">
      <!-- Messages populated by JS -->
    </div>

    <div class="chat-input-row" id="chatInputRow" style="<?= !$user ? 'display:none' : '' ?>">
      <textarea class="chat-input" id="chatInput" placeholder="Type your message…" rows="1" maxlength="1000"></textarea>
      <button class="chat-send" id="chatSend" aria-label="Send">➤</button>
    </div>
  </div>
</div>

<!-- Mouse effects -->
<div class="torch-glow" id="torchGlow"></div>

<!-- Scripts -->
<script src="/assets/js/main.js"></script>
<script>
// Pass PHP vars to JS
const SITE = {
  url: '<?= SITE_URL ?>',
  loggedIn: <?= $user ? 'true' : 'false' ?>,
  userId: <?= $user ? (int)$user['id'] : 'null' ?>,
  username: '<?= h($user['username'] ?? '') ?>',
  csrfToken: '<?= h(csrf_token()) ?>'
};
</script>
<?php if (!empty($extra_js)): ?>
<script src="<?= h($extra_js) ?>"></script>
<?php endif; ?>
</body>
</html>
