<?php
/**
 * GoldOSRS.com – AJAX: Place Bet
 * Validates bet server-side, updates credits, inserts history.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
start_session();

header('Content-Type: application/json; charset=utf-8');

function json_error(string $msg): never {
    echo json_encode(['error' => $msg]);
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method.');
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals(csrf_token(), $token)) {
    json_error('Invalid CSRF token.');
}

// Must be logged in
if (!is_logged_in()) {
    json_error('You must be logged in to place a bet.');
}

$user_id = current_user_id();
$game    = $_POST['game'] ?? '';
$amount  = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

$valid_games = ['dice', 'slots', 'rs3'];
if (!in_array($game, $valid_games, true)) {
    json_error('Invalid game.');
}
if ($amount < MIN_BET) {
    json_error('Minimum bet is ' . MIN_BET . ' credit(s).');
}

$pdo = get_db();

// Lock the user row and check credits
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT credits FROM users WHERE id = :uid FOR UPDATE');
    $stmt->execute([':uid' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        json_error('User not found.');
    }

    $credits = (float)$user['credits'];
    if ($credits < $amount) {
        $pdo->rollBack();
        json_error('Insufficient credits. You have ' . number_format($credits, 2) . ' credits.');
    }

    // --------------------------------------------------------------------------
    // Game logic (server-side, cryptographically secure)
    // --------------------------------------------------------------------------
    $result     = 'loss';
    $win_amount = 0.0;
    $message    = '';
    $extra_data = [];

    switch ($game) {

        // DICE: roll 1–100; over 50 = win at 1.9× (house edge 5%)
        case 'dice':
            $roll = random_int(1, 100);
            $extra_data['roll'] = $roll;
            if ($roll > 50) {
                $result     = 'win';
                $win_amount = round($amount * 1.9, 2);
                $message    = "🎲 Rolled $roll – You win! +" . number_format($win_amount, 2) . ' credits';
            } else {
                $result  = 'loss';
                $message = "🎲 Rolled $roll – Unlucky! You lost " . number_format($amount, 2) . ' credits';
            }
            break;

        // SLOTS: 3 reels; all same = 5×, two same = 1.5×, else lose
        case 'slots':
            $symbols = ['🍒','🍋','🍊','⭐','💎','7️⃣','🔔'];
            $reels   = [
                $symbols[random_int(0, count($symbols)-1)],
                $symbols[random_int(0, count($symbols)-1)],
                $symbols[random_int(0, count($symbols)-1)],
            ];
            $extra_data['reels'] = $reels;

            if ($reels[0] === $reels[1] && $reels[1] === $reels[2]) {
                $result     = 'win';
                $win_amount = round($amount * 5, 2);
                $message    = implode('', $reels) . ' – JACKPOT! +' . number_format($win_amount, 2) . ' credits';
            } elseif ($reels[0] === $reels[1] || $reels[1] === $reels[2] || $reels[0] === $reels[2]) {
                $result     = 'win';
                $win_amount = round($amount * 1.5, 2);
                $message    = implode('', $reels) . ' – Two of a kind! +' . number_format($win_amount, 2) . ' credits';
            } else {
                $result  = 'loss';
                $message = implode('', $reels) . ' – No match. You lost ' . number_format($amount, 2) . ' credits';
            }
            break;

        // RS3 GEMS: 1 of 5 gems hides the Dragon's Hoard (win 3×)
        case 'rs3':
            $gem_index = isset($_POST['gemIndex']) ? (int)$_POST['gemIndex'] : -1;
            if ($gem_index < 0 || $gem_index > 4) {
                $pdo->rollBack();
                json_error('Please select a gem.');
            }
            $win_index = random_int(0, 4); // server chooses winner
            $extra_data['winIndex'] = $win_index;

            if ($gem_index === $win_index) {
                $result     = 'win';
                $win_amount = round($amount * 3, 2);
                $message    = "🐉 Dragon's Hoard found! +" . number_format($win_amount, 2) . ' credits';
            } else {
                $result  = 'loss';
                $message = '💀 Cursed gem! You lost ' . number_format($amount, 2) . ' credits';
            }
            break;
    }

    // --------------------------------------------------------------------------
    // Update credits
    // --------------------------------------------------------------------------
    if ($result === 'win') {
        $new_credits = $credits - $amount + $win_amount;
    } else {
        $new_credits = $credits - $amount;
    }
    $new_credits = max(0.0, $new_credits);

    $pdo->prepare('UPDATE users SET credits = :c WHERE id = :uid')
        ->execute([':c' => $new_credits, ':uid' => $user_id]);

    // --------------------------------------------------------------------------
    // Insert betting history
    // --------------------------------------------------------------------------
    $pdo->prepare(
        'INSERT INTO betting_history (user_id, game, bet_amount, win_amount, result)
         VALUES (:uid, :game, :bet, :win, :res)'
    )->execute([
        ':uid'  => $user_id,
        ':game' => $game,
        ':bet'  => $amount,
        ':win'  => $win_amount,
        ':res'  => $result,
    ]);

    $pdo->commit();

    // Build a history row HTML snippet
    $now = date('Y-m-d H:i');
    $row_html = '<td>' . htmlspecialchars(ucfirst($game), ENT_QUOTES, 'UTF-8') . '</td>'
              . '<td>' . number_format($amount, 2) . '</td>'
              . '<td>' . number_format($win_amount, 2) . '</td>'
              . '<td class="' . $result . '">' . ucfirst($result) . '</td>'
              . '<td>' . htmlspecialchars($now, ENT_QUOTES, 'UTF-8') . '</td>';

    echo json_encode(array_merge([
        'result'      => $result,
        'message'     => $message,
        'credits'     => number_format($new_credits, 2),
        'history_row' => $row_html,
    ], $extra_data));

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error('Server error. Please try again.');
}
