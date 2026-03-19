<?php
/**
 * GoldOSRS.com – Gambling Games Page (Step 3)
 * Games: Dice, Slots, RS3 Gems
 * Bets are submitted via AJAX to ajax/place_bet.php.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session();

$page_title = 'Gambling Games';
// CSRF token in <head> for AJAX requests
$extra_head = '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';

// Fetch betting history for the current user (last 15 bets)
$bet_history = [];
if (is_logged_in()) {
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT game, bet_amount, win_amount, result, created_at
         FROM betting_history
         WHERE user_id = :uid
         ORDER BY created_at DESC
         LIMIT 15'
    );
    $stmt->execute([':uid' => current_user_id()]);
    $bet_history = $stmt->fetchAll();
}

// Fetch current credits
$user_credits = 0.00;
if (is_logged_in()) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT credits FROM users WHERE id = :uid');
    $stmt->execute([':uid' => current_user_id()]);
    $row = $stmt->fetch();
    $user_credits = $row ? (float)$row['credits'] : 0.00;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <h1 class="gold-text text-center" style="margin-bottom:.5rem;">Gambling Games</h1>
    <p class="text-center" style="color:var(--color-grey); margin-bottom:2.5rem;">
        Bet credits and win big. All results are server-side verified.
    </p>

    <?php if (is_logged_in()): ?>
    <p class="text-center" style="margin-bottom:2rem;">
        Your Credits: <strong class="gold-text" id="creditDisplay">
            <?= number_format($user_credits, 2) ?>
        </strong>
    </p>
    <?php else: ?>
    <div class="flash flash-warning" style="max-width:500px; margin:0 auto 2rem;">
        <a href="/login.php">Log in</a> or <a href="/register.php">register</a>
        to place bets with your credits.
    </div>
    <?php endif; ?>

    <!-- Game tabs -->
    <div class="game-tabs" id="gameTabs">
        <button class="game-tab-btn active" onclick="switchGame('dice',  this)">🎲 Dice</button>
        <button class="game-tab-btn"        onclick="switchGame('slots', this)">🎰 Slots</button>
        <button class="game-tab-btn"        onclick="switchGame('rs3',   this)">🐉 RS3 Gems</button>
    </div>

    <!-- ============================================================ -->
    <!--  DICE GAME                                                    -->
    <!-- ============================================================ -->
    <div class="game-panel active" id="panel-dice">
        <div class="game-board">
            <h2 style="margin-bottom:1rem;">Roll the Dice</h2>
            <p style="color:var(--color-grey); font-size:.9rem; margin-bottom:1.25rem;">
                Roll over 50 to win 1.9× your bet. House edge 5%.
            </p>
            <span class="dice-display" id="diceEmoji">🎲</span>
            <div class="game-result" id="diceResult"></div>

            <div class="form-group" style="text-align:left;">
                <label for="diceBet">Bet Amount (credits)</label>
                <input type="number" id="diceBet" min="1" step="1" value="10"
                       style="max-width:200px;">
            </div>

            <button class="btn btn-gold btn-lg" style="margin-top:.75rem;"
                    id="diceBtn" onclick="placeBet('dice')">
                Roll!
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!--  SLOTS GAME                                                   -->
    <!-- ============================================================ -->
    <div class="game-panel" id="panel-slots">
        <div class="game-board">
            <h2 style="margin-bottom:1rem;">Slot Machine</h2>
            <p style="color:var(--color-grey); font-size:.9rem; margin-bottom:1.25rem;">
                Match 3 symbols to win up to 5× your bet!
            </p>
            <div class="slot-reels">
                <div class="slot-reel" id="slot0">🍒</div>
                <div class="slot-reel" id="slot1">🍒</div>
                <div class="slot-reel" id="slot2">🍒</div>
            </div>
            <div class="game-result" id="slotsResult"></div>

            <div class="form-group" style="text-align:left;">
                <label for="slotsBet">Bet Amount (credits)</label>
                <input type="number" id="slotsBet" min="1" step="1" value="10"
                       style="max-width:200px;">
            </div>

            <button class="btn btn-gold btn-lg" style="margin-top:.75rem;"
                    id="slotsBtn" onclick="placeBet('slots')">
                Spin!
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!--  RS3 GEMS GAME                                                -->
    <!-- ============================================================ -->
    <div class="game-panel" id="panel-rs3">
        <div class="game-board">
            <h2 style="margin-bottom:.5rem;">RS3 Gem Pick</h2>
            <p style="color:var(--color-grey); font-size:.9rem; margin-bottom:1.25rem;">
                Pick a gem. One hides the Dragon's Hoard (win 3×).
                The rest are cursed (lose). RuneScape 3 style!
            </p>

            <div class="rs3-gems" id="rs3Gems">
                <?php foreach (['💎','🔮','💠','🪩','💜'] as $i => $gem): ?>
                <div class="rs3-gem" id="gem<?= $i ?>"
                     data-index="<?= $i ?>"
                     onclick="selectGem(this)">
                    <?= $gem ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="game-result" id="rs3Result"></div>

            <div class="form-group" style="text-align:left;">
                <label for="rs3Bet">Bet Amount (credits)</label>
                <input type="number" id="rs3Bet" min="1" step="1" value="10"
                       style="max-width:200px;">
            </div>

            <input type="hidden" id="rs3Selected" value="">

            <button class="btn btn-gold btn-lg" style="margin-top:.75rem;"
                    id="rs3Btn" onclick="placeBet('rs3')" disabled>
                Pick a Gem First
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!--  Betting history                                              -->
    <!-- ============================================================ -->
    <div class="bet-history" id="betHistory">
        <h2 style="font-size:1.3rem; margin-top:3rem; margin-bottom:1rem;">Your Recent Bets</h2>
        <?php if (empty($bet_history) && is_logged_in()): ?>
        <p style="color:var(--color-grey);">No bets yet. Place your first bet above!</p>
        <?php elseif (!is_logged_in()): ?>
        <p style="color:var(--color-grey);">Log in to see your betting history.</p>
        <?php else: ?>
        <table class="bet-table" id="betTable">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Bet</th>
                    <th>Won</th>
                    <th>Result</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bet_history as $bet): ?>
                <tr>
                    <td><?= h(ucfirst($bet['game'])) ?></td>
                    <td><?= number_format((float)$bet['bet_amount'], 2) ?></td>
                    <td><?= number_format((float)$bet['win_amount'], 2) ?></td>
                    <td class="<?= h($bet['result']) ?>"><?= h(ucfirst($bet['result'])) ?></td>
                    <td><?= h(substr($bet['created_at'], 0, 16)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
var loggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;

// --------------------------------------------------------------------------
// Tab switching
// --------------------------------------------------------------------------
function switchGame(id, btn) {
    document.querySelectorAll('.game-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.game-tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');
}

// --------------------------------------------------------------------------
// RS3 gem selection
// --------------------------------------------------------------------------
function selectGem(el) {
    document.querySelectorAll('.rs3-gem').forEach(function(g){ g.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('rs3Selected').value = el.dataset.index;
    var btn = document.getElementById('rs3Btn');
    btn.disabled = false;
    btn.textContent = 'Reveal Gem!';
}

// --------------------------------------------------------------------------
// Place bet via AJAX
// --------------------------------------------------------------------------
function placeBet(game) {
    if (!loggedIn) {
        alert('Please log in to place a bet.');
        window.location.href = '/login.php';
        return;
    }

    var betInput, bet;
    var extra = {};

    if (game === 'dice') {
        betInput = document.getElementById('diceBet');
    } else if (game === 'slots') {
        betInput = document.getElementById('slotsBet');
    } else {
        betInput = document.getElementById('rs3Bet');
        extra.gemIndex = document.getElementById('rs3Selected').value;
    }

    bet = parseFloat(betInput.value);
    if (isNaN(bet) || bet < 1) {
        alert('Please enter a valid bet amount (minimum 1 credit).');
        return;
    }

    // Disable button during request
    var btn = document.getElementById(game === 'rs3' ? 'rs3Btn' : game + 'Btn');
    btn.disabled = true;
    btn.textContent = '…';

    // Animate
    if (game === 'dice') startDiceAnim();
    if (game === 'slots') startSlotsAnim();

    var formData = new FormData();
    formData.append('game',   game);
    formData.append('amount', bet);
    formData.append('csrf_token', getCsrfToken());
    for (var k in extra) formData.append(k, extra[k]);

    fetch('/ajax/place_bet.php', { method: 'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            stopAnims();
            btn.disabled = false;
            btn.textContent = game === 'rs3' ? 'Pick a Gem First' : (game === 'dice' ? 'Roll!' : 'Spin!');

            if (game === 'rs3') {
                // Reset gem selection
                document.querySelectorAll('.rs3-gem').forEach(function(g){ g.classList.remove('selected'); });
                document.getElementById('rs3Selected').value = '';
                btn.disabled = true;
            }

            if (data.error) {
                setResult(game, data.error, '');
                return;
            }

            // Update credit display
            document.getElementById('creditDisplay').textContent = parseFloat(data.credits).toFixed(2);

            // Show result
            setResult(game, data.message, data.result);

            // Update visuals
            if (game === 'dice') {
                document.getElementById('diceEmoji').textContent = diceEmoji(data.roll);
            }
            if (game === 'slots') {
                var reels = data.reels;
                for (var i = 0; i < 3; i++) {
                    document.getElementById('slot' + i).textContent = reels[i];
                }
            }
            if (game === 'rs3') {
                document.getElementById('gem' + data.winIndex).style.borderColor = 'var(--color-gold)';
            }

            // Prepend to history table
            prependHistory(data.history_row);
        })
        .catch(function(err) {
            stopAnims();
            btn.disabled = false;
            btn.textContent = game === 'dice' ? 'Roll!' : (game === 'slots' ? 'Spin!' : 'Pick a Gem First');
            setResult(game, 'Network error. Please try again.', '');
        });
}

function setResult(game, message, cls) {
    var el = document.getElementById(game + 'Result');
    if (!el) el = document.getElementById('rs3Result');
    el.textContent = message;
    el.className = 'game-result' + (cls ? ' ' + cls : '');
}

function diceEmoji(n) {
    var faces = ['⚀','⚁','⚂','⚃','⚄','⚅'];
    // n is 1-100; map to 1-6
    return faces[Math.min(5, Math.floor((n - 1) / 17))];
}

// --------------------------------------------------------------------------
// Dice animation
// --------------------------------------------------------------------------
var diceAnimId;
var DICE_FACES = ['⚀','⚁','⚂','⚃','⚄','⚅'];

function startDiceAnim() {
    var el = document.getElementById('diceEmoji');
    el.classList.add('rolling');
    diceAnimId = setInterval(function(){
        el.textContent = DICE_FACES[Math.floor(Math.random() * 6)];
    }, 80);
}

// --------------------------------------------------------------------------
// Slots animation
// --------------------------------------------------------------------------
var SLOT_SYMS = ['🍒','🍋','🍊','⭐','💎','7️⃣','🔔'];
var slotsAnimIds = [];

function startSlotsAnim() {
    for (var i = 0; i < 3; i++) {
        var el = document.getElementById('slot' + i);
        el.classList.add('spinning');
        (function(el){
            slotsAnimIds.push(setInterval(function(){
                el.textContent = SLOT_SYMS[Math.floor(Math.random() * SLOT_SYMS.length)];
            }, 80));
        }(el));
    }
}

function stopAnims() {
    clearInterval(diceAnimId);
    document.getElementById('diceEmoji').classList.remove('rolling');
    slotsAnimIds.forEach(clearInterval);
    slotsAnimIds = [];
    for (var i = 0; i < 3; i++) {
        document.getElementById('slot' + i).classList.remove('spinning');
    }
}

// --------------------------------------------------------------------------
// Prepend a row to the history table
// --------------------------------------------------------------------------
function prependHistory(row) {
    if (!row) return;
    var tbody = document.querySelector('#betTable tbody');
    if (!tbody) {
        // Table might not exist yet (first bet)
        document.getElementById('betHistory').innerHTML =
            '<h2 style="font-size:1.3rem; margin-top:3rem; margin-bottom:1rem;">Your Recent Bets</h2>' +
            '<table class="bet-table" id="betTable"><thead><tr>' +
            '<th>Game</th><th>Bet</th><th>Won</th><th>Result</th><th>Date</th>' +
            '</tr></thead><tbody id="betTbody"></tbody></table>';
        tbody = document.getElementById('betTbody');
    }
    var tr = document.createElement('tr');
    tr.innerHTML = row;
    tbody.insertBefore(tr, tbody.firstChild);
    // Keep only 15 rows
    while (tbody.children.length > 15) tbody.removeChild(tbody.lastChild);
}

// --------------------------------------------------------------------------
// Get CSRF token from meta tag (injected below)
// --------------------------------------------------------------------------
function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
