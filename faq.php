<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'FAQ | GoldOSRS';
$page_desc  = 'Frequently asked questions about buying OSRS gold, RS3 gold, and RuneScape services from GoldOSRS.';
require_once __DIR__ . '/includes/header.php';
$faqs = [
  ['q'=>'How fast is delivery?','a'=>'Gold orders are typically delivered within 1–5 minutes. Services vary depending on complexity — our team will keep you updated via live chat.'],
  ['q'=>'Is it safe to buy gold/services?','a'=>'We use VPN-protected high-level accounts and undetectable trading methods. We have served thousands of customers since 2018 with zero bans reported.'],
  ['q'=>'What payment methods do you accept?','a'=>'We accept Bitcoin (BTC), Ethereum (ETH), Litecoin (LTC), Visa, Mastercard, and PayPal. Crypto orders receive a 5% discount.'],
  ['q'=>'How does Bitcoin payment work?','a'=>'After placing your order, you\'ll receive a unique BTC address and exact amount to send. Once we detect 1 confirmation on the blockchain, your order is processed automatically.'],
  ['q'=>'What is your refund policy?','a'=>'We offer a full refund if we cannot complete your order. For service orders, if our booster is unable to complete the task, you receive 100% back.'],
  ['q'=>'How do I track my order?','a'=>'Login to your dashboard to see real-time order status. You\'ll also receive an email confirmation and can follow up in the live chat.'],
  ['q'=>'Can I sell my OSRS gold to you?','a'=>'Yes! Visit the Sell Gold page to see current buy rates. We pay via crypto, PayPal or bank transfer within minutes of receiving your gold.'],
  ['q'=>'Are gambling games provably fair?','a'=>'Yes. Every game result uses HMAC-SHA256 with a server seed (revealed after the game), your client seed, and a nonce. You can verify any result in your game history.'],
  ['q'=>'How does the live chat work?','a'=>'Click the chat icon on any page. If you\'re a guest, enter your name to start. Messages are relayed to our Discord support team who respond within minutes 24/7.'],
  ['q'=>'Do you offer bulk discounts?','a'=>'Yes. Orders of 1B+ OSRS GP or 5B+ RS3 GP qualify for bulk rates. Contact us via live chat for a custom quote.'],
];
?>
<main class="page-content">
<section class="page-hero">
  <h1>❓ FAQ</h1>
  <p>Answers to the most common questions.</p>
</section>
<section class="section" style="padding-top:20px">
  <div class="container" style="max-width:800px">
    <?php foreach ($faqs as $i => $f): ?>
    <div class="card mb-16" style="cursor:pointer" onclick="toggleFaq(<?= $i ?>)">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h3 style="font-size:15px"><?= h($f['q']) ?></h3>
        <span id="faqIcon<?= $i ?>" style="color:var(--gold);font-size:20px;flex-shrink:0;margin-left:12px">+</span>
      </div>
      <div id="faqAns<?= $i ?>" style="display:none;margin-top:12px;color:var(--text-muted);font-size:14px;line-height:1.7"><?= h($f['a']) ?></div>
    </div>
    <?php endforeach; ?>
    <div class="text-center mt-32">
      <p class="text-muted mb-16">Still have questions?</p>
      <button class="btn-primary" data-open-chat>💬 Chat with Support</button>
    </div>
  </div>
</section>
<script>
function toggleFaq(i){
  const ans=document.getElementById('faqAns'+i);
  const icon=document.getElementById('faqIcon'+i);
  const open=ans.style.display==='block';
  ans.style.display=open?'none':'block';
  icon.textContent=open?'+':'−';
}
</script>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
