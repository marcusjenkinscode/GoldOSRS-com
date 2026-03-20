<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
bootstrap();
header('Content-Type: application/json');

if (!is_logged_in()) { json_out(['error' => 'Login required'], 401); }
csrf_check();
if (!rate_limit('game_roll', 2)) { json_out(['error' => 'Too many requests'], 429); }
if (get_config('gambling_enabled', '1') !== '1') { json_out(['error' => 'Gambling is currently disabled'], 403); }

$user      = current_user();
$game_type = post('game');
$bet       = (int)post('bet');
$extra     = post('extra');
$currency  = post('currency') === 'rs3' ? 'rs3' : 'osrs';

// RS3 games have a minimum of 20M
$rs3_games = ['rs3dice', 'rs3_coinflip', 'rs3_dice_duel'];
$is_rs3    = in_array($game_type, $rs3_games, true) || $currency === 'rs3';

$valid_games = ['dice','coinflip','blackjack','highlow','rs3dice','roulette','flower_poker','rs3_coinflip','rs3_dice_duel'];
if (!in_array($game_type, $valid_games, true)) { json_out(['error' => 'Invalid game'], 400); }

if ($is_rs3) {
    $min_bet = max(20, (int)get_config('min_bet_rs3', '20'));
    $max_bet = (int)get_config('max_bet_rs3', '5000');
    if ($bet < $min_bet)  { json_out(['error' => "RS3 minimum bet is {$min_bet}M GP"], 400); }
    if ($bet > $max_bet)  { json_out(['error' => "RS3 maximum bet is {$max_bet}M GP"], 400); }
    if ($user['balance_rs3'] < $bet) { json_out(['error' => 'Insufficient RS3 balance'], 400); }
} else {
    $min_bet = (int)get_config('min_bet_osrs', '5');
    $max_bet = (int)get_config('max_bet_osrs', '2000');
    if ($bet < $min_bet)  { json_out(['error' => "Minimum bet is {$min_bet}M GP"], 400); }
    if ($bet > $max_bet)  { json_out(['error' => "Maximum bet is {$max_bet}M GP"], 400); }
    if ($user['balance_osrs'] < $bet) { json_out(['error' => 'Insufficient balance'], 400); }
}

// Generate seeds
$server_seed  = generate_server_seed();
$server_hash  = hash_seed($server_seed);
$client_seed  = post('client_seed') ?: bin2hex(random_bytes(8));
$nonce        = (int)($_SESSION['game_nonce'] ?? 0) + 1;
$_SESSION['game_nonce'] = $nonce;

$roll = provably_fair_roll($server_seed, $client_seed, $nonce, 10000); // 0-9999
$roll_pct = $roll / 100; // 0.00 - 99.99

$won        = false;
$win_amount = 0;
$multiplier = 1.0;
$result_str = '';

switch ($game_type) {
    case 'dice':
        $target = max(2, min(96, (int)$extra)); // 2-96
        $house  = (float)get_config('house_edge_dice', '3') / 100;
        $won    = ($roll_pct < $target);
        $multiplier = round((100 - get_config('house_edge_dice', '3')) / $target, 4);
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        $result_str = number_format($roll_pct, 2) . ($won ? " < {$target} WIN" : " >= {$target} LOSE");
        break;

    case 'coinflip':
        $side  = ($extra === 'tails') ? 'tails' : 'heads';
        $result_face = ($roll_pct < 50) ? 'heads' : 'tails';
        $won   = ($result_face === $side);
        $multiplier = 1.90; // 5% house edge each side
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        $result_str = $result_face . ($won ? ' WIN' : ' LOSE');
        break;

    case 'highlow':
        $guess  = ($extra === 'high') ? 'high' : 'low';
        $number = (int)($roll_pct); // 0-99
        $actual = ($number >= 50) ? 'high' : 'low';
        $won    = ($actual === $guess);
        $streak = (int)($_SESSION['hl_streak'] ?? 0);
        if ($won) {
            $streak++;
            $_SESSION['hl_streak'] = $streak;
        } else {
            $_SESSION['hl_streak'] = 0;
            $streak = 0;
        }
        $multiplier = pow(2, min($streak, 10)); // Cap at 1024x
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        $result_str = "Rolled {$number} ({$actual}) — " . ($won ? "WIN x{$multiplier}" : 'LOSE');
        break;

    case 'rs3dice':
        // Roll 3 dice, each with 6 RS3-themed faces
        $faces = ['🐉','⚔️','🛡️','🪙','💎','🌿'];
        $seed1 = provably_fair_roll($server_seed, $client_seed, $nonce,     6); // 0-5
        $seed2 = provably_fair_roll($server_seed, $client_seed, $nonce + 1, 6);
        $seed3 = provably_fair_roll($server_seed, $client_seed, $nonce + 2, 6);
        $f1 = $faces[$seed1]; $f2 = $faces[$seed2]; $f3 = $faces[$seed3];
        $all_dragon = ($seed1 === 0 && $seed2 === 0 && $seed3 === 0);
        $all_match  = ($seed1 === $seed2 && $seed2 === $seed3);
        $any_match  = ($seed1 === $seed2 || $seed2 === $seed3 || $seed1 === $seed3);
        if ($all_dragon)     { $multiplier = 10.0; $won = true;  $result_str = "Triple Dragon! {$f1}{$f2}{$f3}"; }
        elseif ($all_match)  { $multiplier =  5.0; $won = true;  $result_str = "Triple Match! {$f1}{$f2}{$f3}"; }
        elseif ($any_match)  { $multiplier =  2.0; $won = true;  $result_str = "Pair! {$f1}{$f2}{$f3}"; }
        else                 { $multiplier =  0.0; $won = false; $result_str = "No match {$f1}{$f2}{$f3}"; }
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        // Return early with extra face data
        if ($won) {
            db_exec('UPDATE users SET balance_osrs=balance_osrs+? WHERE id=?', 'ii', $win_amount - $bet, $user['id']);
        } else {
            db_exec('UPDATE users SET balance_osrs=balance_osrs-? WHERE id=?', 'ii', $bet, $user['id']);
        }
        db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            'isidsiisssi', $user['id'], 'rs3dice', $bet, $multiplier, $result_str, $win_amount, (int)$won, $server_seed, $server_hash, $client_seed, $nonce);
        $updated = db_one('SELECT balance_osrs FROM users WHERE id=?', 'i', $user['id']);
        json_out([
            'won'         => $won,
            'roll'        => $roll_pct,
            'result'      => $result_str,
            'win_amount'  => $win_amount,
            'multiplier'  => $multiplier,
            'new_balance' => $updated['balance_osrs'],
            'rs3_faces'   => [$f1, $f2, $f3],
            'server_seed' => $server_seed,
            'server_hash' => $server_hash,
            'client_seed' => $client_seed,
            'nonce'       => $nonce,
        ]);
        break; // json_out exits, but kept for clarity

    case 'blackjack':
        // Simplified server-side blackjack
        $action = $extra; // 'deal', 'hit', 'stand', 'double'
        if ($action === 'deal' || !isset($_SESSION['bj_hand'])) {
            // New hand
            $deck = [];
            $vals = [2,3,4,5,6,7,8,9,10,10,10,10,11];
            for ($i = 0; $i < 52; $i++) $deck[] = $vals[$i % 13];
            shuffle($deck);
            $_SESSION['bj_deck']   = $deck;
            $_SESSION['bj_player'] = [$deck[0], $deck[2]];
            $_SESSION['bj_dealer'] = [$deck[1], $deck[3]];
            $_SESSION['bj_over']   = false;
            $_SESSION['bj_bet']    = $bet;
            $p_total = array_sum($_SESSION['bj_player']);
            $won = false;
            $win_amount = 0;
            $result_str = "Player: {$p_total} | Dealer shows: {$_SESSION['bj_dealer'][0]}";
            json_out([
                'player' => $_SESSION['bj_player'],
                'dealer_show' => $_SESSION['bj_dealer'][0],
                'player_total' => $p_total,
                'result' => $result_str,
                'action' => 'continue',
                'won' => false,
                'win_amount' => 0,
                'server_hash' => $server_hash,
                'client_seed' => $client_seed,
            ]);
        } elseif ($action === 'hit') {
            $card = array_shift($_SESSION['bj_deck']);
            $_SESSION['bj_player'][] = $card;
            $p_total = array_sum($_SESSION['bj_player']);
            if ($p_total > 21) {
                // Bust — capture hand before unset
                $bust_hand = $_SESSION['bj_player'];
                db_exec('UPDATE users SET balance_osrs=balance_osrs-? WHERE id=?', 'ii', $bet, $user['id']);
                db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    'isidsiisssi', $user['id'],'blackjack',$bet,0,"Bust ({$p_total})",0,0,$server_seed,$server_hash,$client_seed,$nonce);
                unset($_SESSION['bj_hand'], $_SESSION['bj_player'], $_SESSION['bj_dealer'], $_SESSION['bj_deck']);
                $updated_bust = db_one('SELECT balance_osrs FROM users WHERE id=?', 'i', $user['id']);
                json_out(['player' => $bust_hand, 'player_total' => $p_total, 'result' => "Bust! ({$p_total})", 'action' => 'bust', 'won' => false, 'win_amount' => 0, 'new_balance' => $updated_bust['balance_osrs']]);
            }
            json_out(['player' => $_SESSION['bj_player'], 'player_total' => $p_total, 'result' => "Player: {$p_total}", 'action' => 'continue', 'won' => false, 'win_amount' => 0]);
        } elseif ($action === 'stand' || $action === 'double') {
            $dealer = $_SESSION['bj_dealer'];
            $deck   = $_SESSION['bj_deck'];
            while (array_sum($dealer) < 17 && !empty($deck)) {
                $dealer[] = array_shift($deck);
            }
            $p_total = array_sum($_SESSION['bj_player']);
            $d_total = array_sum($dealer);
            if ($action === 'double') {
                if ($user['balance_osrs'] < $bet * 2) { json_out(['error' => 'Insufficient balance to double'], 400); }
                $bet = $bet * 2;
            }
            $won = ($p_total <= 21 && ($d_total > 21 || $p_total > $d_total));
            $push = ($p_total === $d_total && $p_total <= 21);
            $multiplier = ($p_total === 21 && count($_SESSION['bj_player']) === 2) ? 2.5 : 2.0;
            $win_amount = $won ? (int)round($bet * $multiplier) : 0;
            $result_str = "Player: {$p_total} | Dealer: {$d_total}";
            if ($push) { $result_str .= ' — Push (tie)'; $win_amount = $bet; }
            elseif ($won) { $result_str .= ' — WIN!'; }
            else { $result_str .= ' — LOSE'; }
            unset($_SESSION['bj_player'], $_SESSION['bj_dealer'], $_SESSION['bj_deck'], $_SESSION['bj_bet']);

            if ($won || $push) {
                db_exec('UPDATE users SET balance_osrs=balance_osrs+? WHERE id=?', 'ii', $win_amount - $bet, $user['id']);
            } else {
                db_exec('UPDATE users SET balance_osrs=balance_osrs-? WHERE id=?', 'ii', $bet, $user['id']);
            }
            db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                'isidsiisssi', $user['id'],'blackjack',$bet,$multiplier,$result_str,$win_amount,(int)$won,$server_seed,$server_hash,$client_seed,$nonce);

            $updated = db_one('SELECT balance_osrs FROM users WHERE id=?', 'i', $user['id']);
            json_out(['player' => $_SESSION['bj_player'] ?? [], 'dealer' => $dealer, 'player_total' => $p_total, 'dealer_total' => $d_total, 'result' => $result_str, 'action' => 'done', 'won' => $won, 'win_amount' => $win_amount, 'new_balance' => $updated['balance_osrs']]);
        }
        break;

    case 'roulette':
        $RED_NUMS   = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
        $num        = (int)($roll_pct / (100/37)); // 0-36
        $num        = min(36, max(0, $num));
        $is_red     = in_array($num, $RED_NUMS);
        $is_green   = ($num === 0);
        $is_black   = !$is_red && !$is_green;
        $is_odd     = ($num % 2 === 1);
        $bet_type   = $extra;
        $won        = false;
        $multiplier = 0;
        if ($bet_type === 'red')    { $won = $is_red;    $multiplier = 1.9; }
        elseif ($bet_type === 'black')  { $won = $is_black;  $multiplier = 1.9; }
        elseif ($bet_type === 'green')  { $won = $is_green;  $multiplier = 14.0; }
        elseif ($bet_type === 'odd')    { $won = ($num > 0 && $is_odd);    $multiplier = 1.9; }
        elseif ($bet_type === 'even')   { $won = ($num > 0 && !$is_odd);   $multiplier = 1.9; }
        elseif ($bet_type === '1-18')   { $won = ($num >= 1 && $num <= 18); $multiplier = 1.9; }
        elseif ($bet_type === '19-36')  { $won = ($num >= 19);              $multiplier = 1.9; }
        elseif ($bet_type === '1st12')  { $won = ($num >= 1 && $num <= 12); $multiplier = 2.8; }
        elseif ($bet_type === '2nd12')  { $won = ($num >= 13 && $num <= 24); $multiplier = 2.8; }
        elseif ($bet_type === '3rd12')  { $won = ($num >= 25 && $num <= 36); $multiplier = 2.8; }
        elseif (is_numeric($bet_type)) {
            $pick = (int)$bet_type;
            $won  = ($num === $pick);
            $multiplier = 35.0;
        }
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        $color_str  = $is_green ? 'Green 🟢' : ($is_red ? 'Red 🔴' : 'Black ⚫');
        $result_str = "Number {$num} ({$color_str}) · Bet: {$bet_type}";
        break;

    case 'flower_poker':
        $fp_flowers_arr = ['🌸','🌺','🌼','🌻','🌹','🌷','🌿']; // 7 unique flowers
        $fp_rolls = [];
        for ($fi = 0; $fi < 5; $fi++) {
            $fp_rolls[] = provably_fair_roll($server_seed, $client_seed, $nonce + $fi, 7); // 0-6
        }
        $fp_emoji = array_map(fn($r) => $fp_flowers_arr[$r], $fp_rolls);
        $fp_counts = array_count_values($fp_rolls);
        arsort($fp_counts);
        $counts = array_values($fp_counts);
        // Rank hand
        if (count($fp_counts) === 1)                                        { $hand = 'Five of a Kind';  $multiplier = 10.0; $won = true; }
        elseif (count($fp_counts) === 2 && $counts[0] === 4)                { $hand = 'Four of a Kind';  $multiplier = 5.0;  $won = true; }
        elseif (count($fp_counts) === 2 && $counts[0] === 3)                { $hand = 'Full House';       $multiplier = 3.0;  $won = true; }
        elseif (count($fp_counts) === 3 && $counts[0] === 3)                { $hand = 'Three of a Kind'; $multiplier = 2.0;  $won = true; }
        elseif (count($fp_counts) === 3 && $counts[0] === 2 && $counts[1] === 2) { $hand = 'Two Pair';   $multiplier = 1.5;  $won = true; }
        elseif (count($fp_counts) === 4)                                    { $hand = 'One Pair';         $multiplier = 1.0;  $won = false; } // push
        else                                                                { $hand = 'Bust';              $multiplier = 0;    $won = false; }
        $win_amount = (int)round($bet * $multiplier);
        if ($hand === 'One Pair') { $win_amount = $bet; } // push — return bet
        $result_str = "{$hand} — " . implode(' ', $fp_emoji);
        // Early return with flower data
        if ($won || $hand === 'One Pair') {
            db_exec('UPDATE users SET balance_osrs=balance_osrs+? WHERE id=?', 'ii', $win_amount - $bet, $user['id']);
        } else {
            db_exec('UPDATE users SET balance_osrs=balance_osrs-? WHERE id=?', 'ii', $bet, $user['id']);
        }
        db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            'isidsiisssi', $user['id'], 'flower_poker', $bet, $multiplier, $result_str, $win_amount, (int)$won, $server_seed, $server_hash, $client_seed, $nonce);
        if ($won) {
            db_insert("INSERT INTO toasts (type,content) VALUES ('real',?)", 's', "🌸 " . ucfirst($user['username']) . " got a {$hand} in Flower Poker!");
        }
        $updated_fp = db_one('SELECT balance_osrs FROM users WHERE id=?', 'i', $user['id']);
        json_out(['won'=>$won, 'result'=>$result_str, 'win_amount'=>$win_amount, 'multiplier'=>$multiplier, 'new_balance'=>$updated_fp['balance_osrs'], 'fp_flowers'=>$fp_emoji, 'server_seed'=>$server_seed, 'server_hash'=>$server_hash, 'client_seed'=>$client_seed, 'nonce'=>$nonce]);
        break;

    case 'rs3_coinflip':
        $side        = ($extra === 'tails') ? 'tails' : 'heads';
        $result_face = ($roll_pct < 50) ? 'heads' : 'tails';
        $won         = ($result_face === $side);
        $multiplier  = 1.90;
        $win_amount  = $won ? (int)round($bet * $multiplier) : 0;
        $result_str  = $result_face . ($won ? ' WIN' : ' LOSE');
        if ($won) { db_exec('UPDATE users SET balance_rs3=balance_rs3+? WHERE id=?','ii',$win_amount-$bet,$user['id']); }
        else      { db_exec('UPDATE users SET balance_rs3=balance_rs3-? WHERE id=?','ii',$bet,$user['id']); }
        db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            'isidsiisssi',$user['id'],'rs3_coinflip',$bet,$multiplier,$result_str,$win_amount,(int)$won,$server_seed,$server_hash,$client_seed,$nonce);
        $u = db_one('SELECT balance_rs3 FROM users WHERE id=?','i',$user['id']);
        json_out(['won'=>$won,'result'=>$result_str,'win_amount'=>$win_amount,'multiplier'=>$multiplier,'new_balance'=>$u['balance_rs3'],'server_hash'=>$server_hash,'client_seed'=>$client_seed,'nonce'=>$nonce]);
        break;

    case 'rs3_dice_duel':
        $target     = max(2, min(96, (int)$extra));
        $won        = ($roll_pct < $target);
        $multiplier = round((100 - (float)get_config('house_edge_dice','3')) / $target, 4);
        $win_amount = $won ? (int)round($bet * $multiplier) : 0;
        $result_str = number_format($roll_pct,2) . ($won ? " < {$target} WIN" : " >= {$target} LOSE");
        if ($won) { db_exec('UPDATE users SET balance_rs3=balance_rs3+? WHERE id=?','ii',$win_amount-$bet,$user['id']); }
        else      { db_exec('UPDATE users SET balance_rs3=balance_rs3-? WHERE id=?','ii',$bet,$user['id']); }
        db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            'isidsiisssi',$user['id'],'rs3_dice_duel',$bet,$multiplier,$result_str,$win_amount,(int)$won,$server_seed,$server_hash,$client_seed,$nonce);
        $u = db_one('SELECT balance_rs3 FROM users WHERE id=?','i',$user['id']);
        json_out(['won'=>$won,'roll'=>$roll_pct,'result'=>$result_str,'win_amount'=>$win_amount,'multiplier'=>$multiplier,'new_balance'=>$u['balance_rs3'],'server_hash'=>$server_hash,'client_seed'=>$client_seed,'nonce'=>$nonce]);
        break;
}

// For standard OSRS games: update balance and insert record
$self_handled = ['blackjack', 'rs3dice', 'flower_poker', 'rs3_coinflip', 'rs3_dice_duel'];
if (!in_array($game_type, $self_handled, true)) {
    if ($won) {
        db_exec('UPDATE users SET balance_osrs=balance_osrs+? WHERE id=?', 'ii', $win_amount - $bet, $user['id']);
        db_insert("INSERT INTO toasts (type, content) VALUES ('real', ?)", 's', "🎲 " . ucfirst($user['username']) . " just won " . fmt_gp($win_amount) . " on " . ucfirst($game_type) . "!");
    } else {
        db_exec('UPDATE users SET balance_osrs=balance_osrs-? WHERE id=?', 'ii', $bet, $user['id']);
    }
    db_insert('INSERT INTO games (user_id,game_type,bet,multiplier,result,win_amount,won,server_seed,server_hash,client_seed,nonce) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        'isidsiisssi', $user['id'], $game_type, $bet, $multiplier, $result_str, $win_amount, (int)$won, $server_seed, $server_hash, $client_seed, $nonce);

    $updated = db_one('SELECT balance_osrs FROM users WHERE id=?', 'i', $user['id']);
    $extra_data = [];
    if ($game_type === 'roulette') $extra_data['roulette_number'] = (int)(provably_fair_roll($server_seed, $client_seed, $nonce, 37));
    json_out(array_merge([
        'won'         => $won,
        'roll'        => $roll_pct,
        'result'      => $result_str,
        'win_amount'  => $win_amount,
        'multiplier'  => $multiplier,
        'new_balance' => $updated['balance_osrs'],
        'server_seed' => $server_seed,
        'server_hash' => $server_hash,
        'client_seed' => $client_seed,
        'nonce'       => $nonce,
    ], $extra_data));
}
