<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/functions.php';
bootstrap();
$page_title = 'OSRS & RS3 Gambling — Dice, Coinflip, Roulette, Blackjack | GoldOSRS';
$page_desc  = 'Play provably fair OSRS and RS3 gambling games. Dice, coinflip, roulette, blackjack, flower poker. Instant GP payouts.';
$user = current_user();

$recent_games = db_all(
    'SELECT g.game_type, g.bet, g.win_amount, g.won, u.username FROM games g JOIN users u ON u.id=g.user_id ORDER BY g.created_at DESC LIMIT 10'
);
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-content">
<section class="page-hero">
  <h1>⚔️ Gambling &amp; Games</h1>
  <p>Provably fair games for OSRS &amp; RS3. Instant GP payouts. Verify every result.</p>
</section>

<?php if (!$user): ?>
<div style="text-align:center;padding:20px 0">
  <div class="card" style="max-width:400px;margin:0 auto;text-align:center">
    <div style="font-size:40px;margin-bottom:12px">🎲</div>
    <h3>Login to Play</h3>
    <p class="text-muted" style="margin:12px 0">You need an account and GP balance to play.</p>
    <div style="display:flex;gap:10px;justify-content:center">
      <a href="/login.php" class="btn-primary">Login</a>
      <a href="/register.php" class="btn-secondary">Register</a>
    </div>
  </div>
</div>
<?php else: ?>
<!-- Game currency selector -->
<div style="max-width:600px;margin:0 auto 12px;padding:0 20px">
  <div style="display:flex;gap:8px;justify-content:center;margin-bottom:12px">
    <button id="modeOSRS" onclick="setGameMode('osrs')" class="btn-gold" style="padding:8px 20px;font-size:13px">⚔️ OSRS</button>
    <button id="modeRS3"  onclick="setGameMode('rs3')"  class="btn-secondary" style="padding:8px 20px;font-size:13px">🐉 RS3</button>
  </div>
  <div class="balance-bar" id="balanceBar">
    <span style="color:var(--text-muted);font-size:13px" id="balLabel">OSRS Balance</span>
    <span id="userBalance" style="color:var(--gold);font-weight:700;font-size:18px;font-family:'Cinzel',serif"><?= fmt_gp((int)$user['balance_osrs']) ?></span>
    <a href="/deposit.php" class="btn-gold" style="padding:6px 12px;font-size:12px">+ Deposit</a>
  </div>
  <div id="rs3MinNote" style="display:none;text-align:center;font-size:12px;color:var(--amber);padding:4px 0">⚠️ RS3 games have a minimum bet of 20M GP</div>
</div>
<?php endif; ?>

<section class="section" style="padding-top:20px">
  <div class="container">
    <!-- OSRS games -->
    <div id="osrsGames">
    <div class="grid-3">
      <div class="card game-card" onclick="<?= $user?'openGame(\'dice\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">🎲</div>
        <h3>Dice Duel</h3>
        <p>Roll dice — pick your target. Higher target = lower payout. Simple, fast, fair.</p>
        <div class="game-odds">Win chance: variable · House edge: 3%</div>
        <div class="game-limits">Min: 5M · Max: 2,000M</div>
        <button class="btn-gold mt-16" style="width:100%">🎲 Play Dice</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'coinflip\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">⚡</div>
        <h3>Coin Flip</h3>
        <p>Heads or tails. Pick your side and double your bet. 47.5% win chance.</p>
        <div class="game-odds">Win chance: 47.5% · House edge: 5%</div>
        <div class="game-limits">Min: 5M · Max: 5,000M</div>
        <button class="btn-gold mt-16" style="width:100%">⚡ Flip Coin</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'blackjack\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">🃏</div>
        <h3>Blackjack</h3>
        <p>Classic 21. Hit, stand, or double down. Beat the dealer without busting.</p>
        <div class="game-odds">Win chance: 49% · House edge: 2%</div>
        <div class="game-limits">Min: 10M · Max: 1,000M</div>
        <button class="btn-gold mt-16" style="width:100%">🃏 Play Blackjack</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'highlow\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">📊</div>
        <h3>High / Low</h3>
        <p>Guess higher or lower than 50. Build streaks — each win doubles your multiplier!</p>
        <div class="game-odds">Streak multiplier: 2x per correct guess</div>
        <div class="game-limits">Min: 5M · Max: 500M</div>
        <button class="btn-gold mt-16" style="width:100%">📊 Play High/Low</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'rs3dice\')':'window.location=\'/login.php\'' ?>" style="border-color:rgba(255,80,0,0.4)">
        <div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000;border-color:rgba(255,80,0,0.3)">🔥 RS3</div>
        <div class="card-icon">🐉</div>
        <h3>RS3 Dragon Dice</h3>
        <p>RuneScape 3 style: roll 3 dragon dice. Matching faces pay 5x — dragon roll pays 10x!</p>
        <div class="game-odds">Win chance: ~30% · Dragon jackpot: 10x</div>
        <div class="game-limits">Min: 5M · Max: 1,000M</div>
        <button class="btn-gold mt-16" style="width:100%">🐉 Roll Dragons</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'roulette\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">🎡</div>
        <h3>Roulette</h3>
        <p>Classic roulette. Bet on red/black, odd/even, or a specific number for 35x!</p>
        <div class="game-odds">Number: 35x · Red/Black: 1.9x · House edge: 2.7%</div>
        <div class="game-limits">Min: 5M · Max: 1,000M</div>
        <button class="btn-gold mt-16" style="width:100%">🎡 Spin Wheel</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'flower_poker\')':'window.location=\'/login.php\'' ?>">
        <div class="card-icon">🌸</div>
        <h3>Flower Poker</h3>
        <p>Classic OSRS flower poker. Plant 5 flowers — pairs, full house, bust and more!</p>
        <div class="game-odds">Win chance: ~47% · Full House: 5x</div>
        <div class="game-limits">Min: 10M · Max: 2,000M</div>
        <button class="btn-gold mt-16" style="width:100%">🌸 Play Flowers</button>
      </div>
      <div class="card" style="border-color:rgba(255,215,0,0.5);background:linear-gradient(135deg,rgba(255,215,0,0.05),var(--bg-card))">
        <div class="card-badge" style="background:rgba(255,215,0,0.15);color:var(--gold)">🔴 LIVE</div>
        <div class="card-icon">🏆</div>
        <h3>Weekly Tournament</h3>
        <p>PvP bracket tournament every Friday. Rules &amp; events in Discord. Prizes tiered for 🥇 1st · 🥈 2nd · 🥉 3rd.</p>
        <div class="game-odds">New draw every Friday</div>
        <a href="https://discord.gg/n9HP7GH2e3" target="_blank" rel="noopener" class="btn-gold mt-16" style="width:100%;display:block">🎮 Join on Discord</a>
      </div>
    </div><!-- /grid-3 OSRS -->
    </div><!-- /#osrsGames -->

    <!-- RS3 Games section -->
    <div id="rs3Games" style="display:none">
    <div class="grid-3">
      <div class="card game-card" onclick="<?= $user?'openGame(\'rs3dice\')':'window.location=\'/login.php\'' ?>" style="border-color:rgba(255,80,0,0.4)">
        <div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000;border-color:rgba(255,80,0,0.3)">🔥 RS3</div>
        <div class="card-icon">🐉</div>
        <h3>RS3 Dragon Dice</h3>
        <p>Roll 3 dragon dice. Matching faces pay 5x — triple dragon pays 10x!</p>
        <div class="game-odds">Win chance: ~30% · Dragon jackpot: 10x</div>
        <div class="game-limits">Min: 20M · Max: 1,000M</div>
        <button class="btn-gold mt-16" style="width:100%;background:linear-gradient(135deg,#c0392b,#e74c3c)">🐉 Roll Dragons</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'rs3_coinflip\')':'window.location=\'/login.php\'' ?>" style="border-color:rgba(255,80,0,0.4)">
        <div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000;border-color:rgba(255,80,0,0.3)">🔥 RS3</div>
        <div class="card-icon">⚡</div>
        <h3>RS3 Coin Flip</h3>
        <p>Heads or tails with RS3 gold. Same 47.5% win chance, higher minimum.</p>
        <div class="game-odds">Win chance: 47.5% · House edge: 5%</div>
        <div class="game-limits">Min: 20M · Max: 5,000M</div>
        <button class="btn-gold mt-16" style="width:100%;background:linear-gradient(135deg,#c0392b,#e74c3c)">⚡ Flip Coin (RS3)</button>
      </div>
      <div class="card game-card" onclick="<?= $user?'openGame(\'rs3_dice_duel\')':'window.location=\'/login.php\'' ?>" style="border-color:rgba(255,80,0,0.4)">
        <div class="card-badge" style="background:rgba(255,80,0,0.15);color:#ff5000;border-color:rgba(255,80,0,0.3)">🔥 RS3</div>
        <div class="card-icon">🎲</div>
        <h3>RS3 Dice Duel</h3>
        <p>Classic dice roll with RS3 gold. Pick your target and win.</p>
        <div class="game-odds">Win chance: variable · House edge: 3%</div>
        <div class="game-limits">Min: 20M · Max: 2,000M</div>
        <button class="btn-gold mt-16" style="width:100%;background:linear-gradient(135deg,#c0392b,#e74c3c)">🎲 Roll Dice (RS3)</button>
      </div>
    </div>
    </div><!-- /#rs3Games -->

    <!-- Recent results -->
    <div class="mt-32" style="max-width:700px;margin:32px auto 0">
      <h2 class="section-title" style="font-size:22px;margin-bottom:16px">⚡ Recent Results</h2>
      <div class="results-feed" id="liveResults">
        <?php foreach ($recent_games as $g): ?>
        <div class="feed-item">
          <span>🎲 <strong><?= h($g['username']) ?></strong> · <?= h(ucfirst($g['game_type'])) ?> · <?= fmt_gp((int)$g['bet']) ?></span>
          <span class="<?= $g['won']?'feed-won':'feed-lost' ?>"><?= $g['won']?'WON +'.fmt_gp((int)$g['win_amount']):'LOST' ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($recent_games)): ?>
        <div class="text-muted text-center" style="padding:16px">No games played yet. Be the first!</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Game Modal -->
<?php if ($user): ?>
<div id="gameModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:60000;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <h2 id="modalTitle" class="text-gold" style="font-family:'Cinzel',serif;font-size:18px"></h2>
      <button onclick="closeGame()" style="background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer">✕</button>
    </div>
    <div id="gameContent" style="padding:24px"></div>
  </div>
</div>

<script>
const CSRF = '<?= h(csrf_token()) ?>';
let currentGame   = null;
let gameMode      = 'osrs'; // 'osrs' or 'rs3'
let currentBalance     = <?= (int)$user['balance_osrs'] ?>;
let currentBalanceRS3  = <?= (int)$user['balance_rs3']  ?>;

function setGameMode(mode) {
  gameMode = mode;
  document.getElementById('osrsGames').style.display = mode === 'osrs' ? '' : 'none';
  document.getElementById('rs3Games').style.display  = mode === 'rs3'  ? '' : 'none';
  document.getElementById('modeOSRS').className = mode === 'osrs' ? 'btn-gold'      : 'btn-secondary';
  document.getElementById('modeRS3').className  = mode === 'rs3'  ? 'btn-gold'      : 'btn-secondary';
  document.getElementById('balLabel').textContent = mode === 'osrs' ? 'OSRS Balance' : 'RS3 Balance';
  document.getElementById('userBalance').textContent = fmtM(mode === 'osrs' ? currentBalance : currentBalanceRS3);
  document.getElementById('rs3MinNote').style.display = mode === 'rs3' ? '' : 'none';
}

const games = {
  dice: {
    title: '🎲 Dice Duel',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div class="dice-display" id="diceDisplay">🎲</div>
          <p class="text-muted" style="font-size:13px;margin-bottom:12px">Roll UNDER your target to win</p>
          <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px">Target (2–96)</label>
          <input id="diceTarget" type="range" min="2" max="96" value="50" style="width:80%;accent-color:var(--gold)">
          <div style="margin:4px 0 12px;font-size:13px;color:var(--gold)" id="diceInfo">Target: 50 · Win chance: 50% · Payout: 1.47x</div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="10" min="5" max="2000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(5)">5M</button><button onclick="setBet(50)">50M</button><button onclick="setBet(100)">100M</button><button onclick="setBetHalf()">½</button><button onclick="setBetMax()">Max</button></div>
          <button onclick="rollDice()" class="btn-primary" style="width:80%;margin-top:16px" id="rollBtn">🎲 Roll</button>
          <div id="diceResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  coinflip: {
    title: '⚡ Coin Flip',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div style="font-size:80px;margin:12px 0" id="coinDisplay">🪙</div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="10" min="5" max="5000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(5)">5M</button><button onclick="setBet(50)">50M</button><button onclick="setBet(500)">500M</button><button onclick="setBetHalf()">½</button><button onclick="setBetMax()">Max</button></div>
          <div style="display:flex;gap:12px;justify-content:center;margin-top:20px">
            <button onclick="flipCoin('heads')" class="btn-primary" style="flex:1;max-width:160px">Heads 👑</button>
            <button onclick="flipCoin('tails')" class="btn-secondary" style="flex:1;max-width:160px">Tails ⚔️</button>
          </div>
          <div id="flipResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  highlow: {
    title: '📊 High / Low',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div style="font-size:64px;margin:12px 0;font-family:'Cinzel Decorative',serif;color:var(--gold)" id="hlNumber">?</div>
          <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px">Streak: <span id="hlStreak" style="color:var(--gold);font-weight:700">0</span> · Multiplier: <span id="hlMult" style="color:var(--amber)">1x</span></div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="10" min="5" max="500" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(5)">5M</button><button onclick="setBet(25)">25M</button><button onclick="setBet(100)">100M</button><button onclick="setBetHalf()">½</button></div>
          <div style="display:flex;gap:12px;justify-content:center;margin-top:20px">
            <button onclick="guessHL('high')" class="btn-primary" style="flex:1;max-width:160px">📈 High (50+)</button>
            <button onclick="guessHL('low')" class="btn-secondary" style="flex:1;max-width:160px">📉 Low (0–49)</button>
          </div>
          <div id="hlResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  blackjack: {
    title: '🃏 Blackjack',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div id="bjTable" class="text-center">
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="10" min="10" max="1000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(10)">10M</button><button onclick="setBet(50)">50M</button><button onclick="setBet(200)">200M</button><button onclick="setBetHalf()">½</button></div>
          <button onclick="bjDeal()" class="btn-primary" style="width:80%;margin-top:16px" id="dealBtn">🃏 Deal</button>
          <div id="bjActions" style="display:none;margin-top:16px;gap:10px;justify-content:center">
            <button onclick="bjAction('hit')" class="btn-primary">Hit</button>
            <button onclick="bjAction('stand')" class="btn-secondary">Stand</button>
            <button onclick="bjAction('double')" class="btn-secondary">Double</button>
          </div>
          <div id="bjInfo" style="margin-top:16px;font-size:15px;min-height:28px"></div>
          <div id="bjResult" style="margin-top:8px;font-size:20px;min-height:28px"></div>
        </div>`;
    }
  },
  rs3dice: {
    title: '🐉 RS3 Dragon Dice',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div style="display:flex;justify-content:center;gap:16px;font-size:64px;margin:12px 0" id="rs3DiceDisplay">
            <span id="d1">🎲</span><span id="d2">🎲</span><span id="d3">🎲</span>
          </div>
          <p class="text-muted" style="font-size:12px;margin-bottom:4px">3 matching faces = 5x · All dragons (🐉) = 10x · Any match = 2x</p>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="10" min="5" max="1000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(5)">5M</button><button onclick="setBet(50)">50M</button><button onclick="setBet(200)">200M</button><button onclick="setBetHalf()">½</button><button onclick="setBetMax()">Max</button></div>
          <button onclick="rollRS3Dice()" class="btn-primary" style="width:80%;margin-top:16px;background:linear-gradient(135deg,#c0392b,#e74c3c)" id="rs3Btn">🐉 Roll Dragons</button>
          <div id="rs3Result" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  roulette: {
    title: '🎡 Roulette',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:16px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div style="font-size:72px;margin:10px 0" id="rouletteDisplay">🎡</div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label>
            <input id="betAmt" type="number" value="10" min="5" max="1000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none">
          </div>
          <div class="bet-quick"><button onclick="setBet(5)">5M</button><button onclick="setBet(50)">50M</button><button onclick="setBet(200)">200M</button><button onclick="setBetHalf()">½</button><button onclick="setBetMax()">Max</button></div>
          <p class="text-muted" style="font-size:12px;margin:10px 0 6px">Choose your bet type:</p>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:12px">
            <button onclick="spinRoulette('red')"    class="btn-secondary" style="background:rgba(231,76,60,0.15);border-color:#e74c3c;color:#e74c3c">🔴 Red (1.9x)</button>
            <button onclick="spinRoulette('black')"  class="btn-secondary" style="color:var(--text)">⚫ Black (1.9x)</button>
            <button onclick="spinRoulette('green')"  class="btn-secondary" style="background:rgba(39,174,96,0.15);border-color:#27ae60;color:#27ae60">🟢 Green (14x)</button>
            <button onclick="spinRoulette('odd')"    class="btn-secondary">Odd (1.9x)</button>
            <button onclick="spinRoulette('even')"   class="btn-secondary">Even (1.9x)</button>
            <button onclick="spinRoulette('1-18')"   class="btn-secondary">1–18 (1.9x)</button>
            <button onclick="spinRoulette('19-36')"  class="btn-secondary">19–36 (1.9x)</button>
            <button onclick="spinRoulette('1st12')"  class="btn-secondary">1st 12 (2.8x)</button>
            <button onclick="spinRoulette('2nd12')"  class="btn-secondary">2nd 12 (2.8x)</button>
          </div>
          <div style="margin-bottom:8px">
            <label style="font-size:12px;color:var(--text-muted)">Or pick a specific number (0–36) for 35x:</label>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:6px">
              <input id="rouletteNum" type="number" min="0" max="36" placeholder="0–36" style="width:90px;padding:8px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;text-align:center;outline:none">
              <button onclick="spinRoulette('number')" class="btn-gold" style="padding:8px 14px">Bet Number</button>
            </div>
          </div>
          <div id="rouletteResult" style="margin-top:12px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  flower_poker: {
    title: '🌸 Flower Poker',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:16px"><span class="text-muted" style="font-size:13px">Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalance)}</span></div>
        <div class="text-center">
          <div style="display:flex;justify-content:center;gap:10px;font-size:48px;margin:10px 0" id="fpFlowers">
            <span>🌸</span><span>🌸</span><span>🌸</span><span>🌸</span><span>🌸</span>
          </div>
          <p class="text-muted" style="font-size:12px;margin-bottom:10px">5 flowers are planted — pairs, 3-of-a-kind, full house or bust!</p>
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:12px;line-height:1.7">
            🌸 Bust (no match) → 0x &nbsp;|&nbsp; One Pair → push (tie) &nbsp;|&nbsp; Two Pair → 1.5x<br>
            Three of a Kind → 2x &nbsp;|&nbsp; Full House → 3x &nbsp;|&nbsp; Four of a Kind → 5x &nbsp;|&nbsp; Five of a Kind → 10x
          </div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label>
            <input id="betAmt" type="number" value="10" min="10" max="2000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none">
          </div>
          <div class="bet-quick"><button onclick="setBet(10)">10M</button><button onclick="setBet(100)">100M</button><button onclick="setBet(500)">500M</button><button onclick="setBetHalf()">½</button></div>
          <button onclick="playFlowerPoker()" class="btn-primary" style="width:80%;margin-top:16px" id="fpBtn">🌸 Plant Flowers</button>
          <div id="fpResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  rs3_coinflip: {
    title: '⚡ RS3 Coin Flip',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">RS3 Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalanceRS3)}</span></div>
        <div class="text-center">
          <div style="font-size:80px;margin:12px 0" id="coinDisplay">🪙</div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="20" min="20" max="5000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(20)">20M</button><button onclick="setBet(100)">100M</button><button onclick="setBet(500)">500M</button><button onclick="setBetHalf()">½</button></div>
          <div style="display:flex;gap:12px;justify-content:center;margin-top:20px">
            <button onclick="flipCoinRS3('heads')" class="btn-primary" style="flex:1;max-width:160px;background:linear-gradient(135deg,#c0392b,#e74c3c)">Heads 👑</button>
            <button onclick="flipCoinRS3('tails')" class="btn-secondary" style="flex:1;max-width:160px">Tails ⚔️</button>
          </div>
          <div id="flipResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  },
  rs3_dice_duel: {
    title: '🎲 RS3 Dice Duel',
    render() {
      return `
        <div class="balance-bar" style="margin-bottom:20px"><span class="text-muted" style="font-size:13px">RS3 Balance</span><span id="gBal" style="color:var(--gold);font-weight:700">${fmtM(currentBalanceRS3)}</span></div>
        <div class="text-center">
          <div class="dice-display" id="diceDisplay">🎲</div>
          <p class="text-muted" style="font-size:13px;margin-bottom:12px">Roll UNDER your target to win</p>
          <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px">Target (2–96)</label>
          <input id="diceTarget" type="range" min="2" max="96" value="50" style="width:80%;accent-color:#c0392b">
          <div style="margin:4px 0 12px;font-size:13px;color:var(--gold)" id="diceInfo">Target: 50 · Win chance: 50% · Payout: 1.47x</div>
          <div class="form-group" style="max-width:220px;margin:0 auto 12px"><label>Bet (M GP)</label><input id="betAmt" type="number" value="20" min="20" max="2000" style="text-align:center;padding:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Cinzel',serif;font-size:14px;width:100%;outline:none"></div>
          <div class="bet-quick"><button onclick="setBet(20)">20M</button><button onclick="setBet(100)">100M</button><button onclick="setBet(500)">500M</button><button onclick="setBetHalf()">½</button></div>
          <button onclick="rollDiceRS3()" class="btn-primary" style="width:80%;margin-top:16px;background:linear-gradient(135deg,#c0392b,#e74c3c)" id="rollBtn">🎲 Roll</button>
          <div id="diceResult" style="margin-top:16px;font-size:18px;min-height:28px"></div>
        </div>`;
    }
  }
};

async function rollRS3Dice() {
  const btn = document.getElementById('rs3Btn');
  btn.disabled = true; btn.textContent = 'Rolling…';
  const FACES = ['🐉','⚔️','🛡️','🪙','💎','🌿'];
  ['d1','d2','d3'].forEach(id => { document.getElementById(id).textContent = '🔄'; });
  const result = await gameRoll('rs3dice', '');
  setTimeout(() => {
    btn.disabled = false; btn.textContent = '🐉 Roll Dragons';
    if (result?.error) { alert(result.error); ['d1','d2','d3'].forEach(id => { document.getElementById(id).textContent = '🎲'; }); return; }
    if (result) {
      const faces = result.rs3_faces || [FACES[0], FACES[1], FACES[2]];
      document.getElementById('d1').textContent = faces[0];
      document.getElementById('d2').textContent = faces[1];
      document.getElementById('d3').textContent = faces[2];
      currentBalance = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalance);
      document.getElementById('rs3Result').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('rs3Result').textContent = result.won ? `✅ ${result.result} — Won ${fmtM(result.win_amount)}!` : `❌ ${result.result}`;
    }
  }, 900);
}

// ── Roulette ─────────────────────────────────────────────────────────────────
const ROULETTE_RED = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
async function spinRoulette(betType) {
  const bet = getBet(); if (!bet || bet < 5) { alert('Minimum bet is 5M'); return; }
  if (bet > currentBalance) { alert('Insufficient balance!'); return; }
  const numInput = document.getElementById('rouletteNum');
  const extraVal = betType === 'number' ? (numInput?.value || '0') : betType;
  document.getElementById('rouletteDisplay').textContent = '🔄';
  document.getElementById('rouletteResult').textContent = '…';
  const result = await gameRoll('roulette', extraVal);
  if (!result) return;
  if (result.error) { alert(result.error); document.getElementById('rouletteDisplay').textContent = '🎡'; document.getElementById('rouletteResult').textContent = ''; return; }
  currentBalance = result.new_balance;
  document.getElementById('gBal').textContent = fmtM(currentBalance);
  document.getElementById('userBalance').textContent = fmtM(currentBalance);
  const num = result.roulette_number;
  const isRed = ROULETTE_RED.includes(num);
  document.getElementById('rouletteDisplay').textContent = num === 0 ? '🟢' : (isRed ? '🔴' : '⚫');
  document.getElementById('rouletteDisplay').textContent += ' ' + num;
  document.getElementById('rouletteResult').className = result.won ? 'result-won' : 'result-lost';
  document.getElementById('rouletteResult').textContent = result.won ? `✅ Won ${fmtM(result.win_amount)}! (${result.result})` : `❌ ${result.result}`;
}

// ── Flower Poker ──────────────────────────────────────────────────────────────
const FP_FLOWERS = ['🌸','🌺','🌼','🌻','🌹','🌷','🌿'];
async function playFlowerPoker() {
  const btn = document.getElementById('fpBtn');
  btn.disabled = true; btn.textContent = 'Planting…';
  const flowers = document.getElementById('fpFlowers');
  FP_FLOWERS.forEach((_, i) => { if (flowers.children[i]) flowers.children[i].textContent = '🔄'; });
  const result = await gameRoll('flower_poker', '');
  setTimeout(() => {
    btn.disabled = false; btn.textContent = '🌸 Plant Flowers';
    if (result?.error) { alert(result.error); [...flowers.children].forEach(c => c.textContent = '🌸'); return; }
    if (result) {
      currentBalance = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalance);
      document.getElementById('userBalance').textContent = fmtM(currentBalance);
      const fp = result.fp_flowers || ['🌸','🌺','🌼','🌻','🌹'];
      [...flowers.children].forEach((c,i) => c.textContent = fp[i] || '🌸');
      document.getElementById('fpResult').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('fpResult').textContent = result.won ? `✅ ${result.result} — Won ${fmtM(result.win_amount)}!` : `❌ ${result.result}`;
    }
  }, 900);
}

// ── RS3 game wrappers ─────────────────────────────────────────────────────────
async function flipCoinRS3(side) {
  const coin = document.getElementById('coinDisplay');
  coin.textContent = '🔄'; coin.style.animation = 'diceRoll 0.6s linear infinite';
  const bet = getBet(); if (bet < 20) { alert('RS3 minimum bet is 20M'); coin.textContent='🪙'; coin.style.animation=''; return; }
  const result = await gameRollRS3('coinflip', side);
  setTimeout(() => {
    coin.style.animation = '';
    if (result?.error) { alert(result.error); coin.textContent = '🪙'; return; }
    if (result) {
      currentBalanceRS3 = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalanceRS3);
      document.getElementById('userBalance').textContent = fmtM(currentBalanceRS3);
      coin.textContent = result.result?.includes('heads') ? '👑' : '⚔️';
      document.getElementById('flipResult').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('flipResult').textContent = result.won ? `✅ Won ${fmtM(result.win_amount)}!` : `❌ ${result.result}`;
    }
  }, 700);
}

async function rollDiceRS3() {
  const target = document.getElementById('diceTarget').value;
  const btn = document.getElementById('rollBtn');
  btn.disabled = true; btn.textContent = 'Rolling…';
  const bet = getBet(); if (bet < 20) { alert('RS3 minimum bet is 20M'); btn.disabled=false; btn.textContent='🎲 Roll'; return; }
  const d = document.getElementById('diceDisplay');
  d.classList.add('rolling');
  const result = await gameRollRS3('dice', target);
  setTimeout(() => {
    d.classList.remove('rolling');
    btn.disabled = false; btn.textContent = '🎲 Roll';
    if (result?.error) { alert(result.error); return; }
    if (result) {
      currentBalanceRS3 = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalanceRS3);
      document.getElementById('userBalance').textContent = fmtM(currentBalanceRS3);
      document.getElementById('diceResult').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('diceResult').textContent = result.won ? `✅ Won ${fmtM(result.win_amount)}!` : `❌ Rolled ${result.roll?.toFixed(2)}`;
    }
  }, 800);
}

async function gameRollRS3(game, extra) {
  const bet = getBet();
  if (bet < 20) { alert('RS3 minimum bet is 20M'); return null; }
  if (bet > currentBalanceRS3) { alert('Insufficient RS3 balance!'); return null; }
  const fd = new FormData();
  fd.append('csrf', CSRF); fd.append('game', game); fd.append('bet', bet); fd.append('extra', extra||''); fd.append('currency', 'rs3');
  const r = await fetch('/api/game_roll.php', { method:'POST', body:fd });
  return r.json();
}

function openGame(g) {
  currentGame = g;
  if (!games[g]) { console.warn('Unknown game:', g); return; }
  document.getElementById('modalTitle').textContent = games[g].title;
  document.getElementById('gameContent').innerHTML = games[g].render();
  document.getElementById('gameModal').style.display = 'flex';
  if (g === 'dice' || g === 'rs3_dice_duel') {
    const tgt = document.getElementById('diceTarget');
    if (tgt) { tgt.oninput = updateDiceInfo; updateDiceInfo(); }
  }
}
function closeGame() { document.getElementById('gameModal').style.display = 'none'; currentGame = null; }
function fmtM(n) { return n>=1000?(n/1000).toFixed(1)+'B GP':n+'M GP'; }
function getBet() { return parseInt(document.getElementById('betAmt')?.value)||0; }
function setBet(n) { if(document.getElementById('betAmt'))document.getElementById('betAmt').value=n; }
function setBetHalf() { setBet(Math.max(5,Math.floor(getBet()/2))); }
function setBetMax() { setBet(Math.min(currentBalance,2000)); }
function updateDiceInfo() {
  const t=parseInt(document.getElementById('diceTarget').value);
  const chance=t; const payout=((100-3)/t).toFixed(2);
  document.getElementById('diceInfo').textContent=`Target: ${t} · Win chance: ${chance}% · Payout: ${payout}x`;
}

async function gameRoll(game, extra) {
  const bet = getBet();
  if (!bet || bet < 5) { alert('Minimum bet is 5M'); return null; }
  if (bet > currentBalance) { alert('Insufficient balance!'); return null; }
  const fd = new FormData();
  fd.append('csrf', CSRF); fd.append('game', game); fd.append('bet', bet); fd.append('extra', extra||'');
  const r = await fetch('/api/game_roll.php', { method:'POST', body:fd });
  return r.json();
}

async function rollDice() {
  const target = document.getElementById('diceTarget').value;
  const btn = document.getElementById('rollBtn');
  btn.disabled = true; btn.textContent = 'Rolling…';
  const d = document.getElementById('diceDisplay');
  d.classList.add('rolling');
  const result = await gameRoll('dice', target);
  setTimeout(() => {
    d.classList.remove('rolling');
    btn.disabled = false; btn.textContent = '🎲 Roll';
    if (result?.error) { alert(result.error); return; }
    if (result) {
      currentBalance = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalance);
      document.getElementById('diceResult').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('diceResult').textContent = result.won ? `✅ Won ${fmtM(result.win_amount)}!` : `❌ Lost — rolled ${result.roll?.toFixed(2)}`;
    }
  }, 800);
}

async function flipCoin(side) {
  const coin = document.getElementById('coinDisplay');
  coin.textContent = '🔄'; coin.style.animation = 'diceRoll 0.6s linear infinite';
  const result = await gameRoll('coinflip', side);
  setTimeout(() => {
    coin.style.animation = '';
    if (result?.error) { alert(result.error); coin.textContent = '🪙'; return; }
    if (result) {
      currentBalance = result.new_balance;
      document.getElementById('gBal').textContent = fmtM(currentBalance);
      coin.textContent = result.result?.includes('heads') ? '👑' : '⚔️';
      document.getElementById('flipResult').className = result.won ? 'result-won' : 'result-lost';
      document.getElementById('flipResult').textContent = result.won ? `✅ ${result.result} — Won ${fmtM(result.win_amount)}!` : `❌ ${result.result}`;
    }
  }, 700);
}

let hlStreak = 0;
async function guessHL(guess) {
  const result = await gameRoll('highlow', guess);
  if (result?.error) { alert(result.error); return; }
  if (result) {
    currentBalance = result.new_balance;
    document.getElementById('gBal').textContent = fmtM(currentBalance);
    if (result.won) hlStreak++; else hlStreak = 0;
    document.getElementById('hlStreak').textContent = hlStreak;
    document.getElementById('hlMult').textContent = Math.pow(2, Math.min(hlStreak,10)) + 'x';
    document.getElementById('hlNumber').textContent = result.roll?.toFixed(0) || '?';
    document.getElementById('hlResult').className = result.won ? 'result-won' : 'result-lost';
    document.getElementById('hlResult').textContent = result.won ? `✅ Won ${fmtM(result.win_amount)}!` : `❌ ${result.result}`;
  }
}

let bjActive = false;
async function bjDeal() {
  const fd = new FormData();
  fd.append('csrf', CSRF); fd.append('game', 'blackjack'); fd.append('bet', getBet()); fd.append('extra', 'deal');
  const r = await fetch('/api/game_roll.php', { method:'POST', body:fd });
  const d = await r.json();
  if (d.error) { alert(d.error); return; }
  bjActive = true;
  document.getElementById('dealBtn').style.display = 'none';
  document.getElementById('bjActions').style.display = 'flex';
  document.getElementById('bjActions').style.flexDirection = 'row';
  document.getElementById('bjInfo').textContent = d.result;
  document.getElementById('bjResult').textContent = '';
}

async function bjAction(action) {
  const fd = new FormData();
  fd.append('csrf', CSRF); fd.append('game', 'blackjack'); fd.append('bet', getBet()); fd.append('extra', action);
  const r = await fetch('/api/game_roll.php', { method:'POST', body:fd });
  const d = await r.json();
  if (d.error) { alert(d.error); return; }
  document.getElementById('bjInfo').textContent = d.result || '';
  if (d.action === 'bust' || d.action === 'done') {
    bjActive = false;
    if (d.new_balance !== undefined) { currentBalance = d.new_balance; document.getElementById('gBal').textContent = fmtM(currentBalance); }
    document.getElementById('bjResult').className = d.won ? 'result-won' : 'result-lost';
    document.getElementById('bjResult').textContent = d.won ? `✅ Won ${fmtM(d.win_amount)}!` : `❌ ${d.result}`;
    document.getElementById('bjActions').style.display = 'none';
    document.getElementById('dealBtn').style.display = '';
    document.getElementById('dealBtn').textContent = '🃏 Deal Again';
  }
}
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
