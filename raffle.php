<?php
/**
 * GoldOSRS.com – Raffle Page (Step 4)
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

$page_title = 'Weekly Raffle';

// Fetch prize pool total from DB
$pool_total = 0.0;
try {
    $pdo = get_db();
    $row = $pdo->query('SELECT COALESCE(SUM(value), 0) AS total FROM raffle_prizes')->fetch();
    $pool_total = (float)($row['total'] ?? 0);
} catch (Throwable $e) {
    // DB not available – show placeholder
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="raffle-hero">
        <h1 class="gold-text" style="font-size:clamp(2rem,6vw,3.5rem); margin-bottom:.5rem;">
            🎁 Weekly Raffle
        </h1>
        <p style="color:var(--color-grey); font-size:1.1rem; margin-bottom:1.5rem;">
            Enter for a chance to win incredible RuneScape items and gold!
        </p>

        <!-- Prize pool display -->
        <div class="prize-pool-display">
            Prize Pool: <?= format_gp($pool_total) ?>
        </div>
        <p style="color:var(--color-grey); font-size:.88rem; margin-bottom:2rem;">
            Total estimated value of prizes this week
        </p>

        <!-- Chest (click to reveal prizes) -->
        <div class="chest-wrapper" onclick="toggleChest()" onkeydown="if(event.key==='Enter'||event.key===' ')toggleChest()"
             role="button" tabindex="0"
             aria-expanded="false" id="chestWrapper" aria-label="Open chest to reveal prizes">
            <span class="chest-icon" id="chestIcon">📦</span>
            <p class="chest-hint" id="chestHint">Click the chest to reveal prizes!</p>
        </div>

        <!-- Prize inventory (hidden until chest clicked) -->
        <div class="prize-inventory" id="prizeInventory">
            <h3 style="margin-bottom:1rem;">This Week's Prizes</h3>
            <div class="prize-list" id="prizeList">
                <div style="padding:1.5rem; color:var(--color-grey); text-align:center;">
                    Loading prizes…
                </div>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div style="max-width:700px; margin:0 auto 4rem; background:var(--color-bg-card);
                border:1px solid var(--color-border); border-radius:6px; padding:2rem;">
        <h2 style="margin-bottom:1rem;">How the Raffle Works</h2>
        <ol style="padding-left:1.5rem; line-height:2; color:var(--color-white);">
            <li>Purchase a raffle ticket from the <a href="/order.php?service=raffle-ticket&price=4.99">order page</a>.</li>
            <li>Each ticket gives you one entry into this week's raffle.</li>
            <li>Winners are drawn every Sunday at 20:00 UTC.</li>
            <li>Prizes are delivered to your RSN within 24 hours of the draw.</li>
        </ol>
        <div style="margin-top:1.5rem;">
            <a href="/order.php?service=raffle-ticket&price=4.99" class="btn btn-gold">
                🎟 Buy a Ticket – $4.99
            </a>
        </div>
    </div>
</div>

<script>
var chestOpen = false;

function toggleChest() {
    var icon    = document.getElementById('chestIcon');
    var inv     = document.getElementById('prizeInventory');
    var hint    = document.getElementById('chestHint');
    var wrapper = document.getElementById('chestWrapper');

    chestOpen = !chestOpen;
    icon.classList.toggle('open', chestOpen);
    wrapper.setAttribute('aria-expanded', chestOpen ? 'true' : 'false');

    if (chestOpen) {
        icon.textContent = '🎁';
        hint.textContent = 'Click to close';
        inv.classList.add('visible');
        loadPrizes();
    } else {
        icon.textContent = '📦';
        hint.textContent = 'Click the chest to reveal prizes!';
        inv.classList.remove('visible');
    }
}

function loadPrizes() {
    var list = document.getElementById('prizeList');
    list.innerHTML = '<div style="padding:1.5rem; color:var(--color-grey); text-align:center;">Loading…</div>';

    fetch('/ajax/get_prizes.php')
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.prizes || data.prizes.length === 0) {
                list.innerHTML = '<div style="padding:1.5rem; color:var(--color-grey); text-align:center;">No prizes listed yet.</div>';
                return;
            }
            list.innerHTML = '';
            data.prizes.forEach(function(p) {
                var item = document.createElement('div');
                item.className = 'prize-item';
                item.innerHTML =
                    '<span class="prize-item-name">🏆 ' + escHtml(p.name) + '</span>' +
                    '<span class="prize-item-value">' + escHtml(p.value_fmt) + '</span>';
                list.appendChild(item);
            });
        })
        .catch(function() {
            list.innerHTML = '<div style="padding:1.5rem; color:var(--color-error); text-align:center;">Failed to load prizes.</div>';
        });
}

function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
