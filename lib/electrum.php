<?php
// lib/electrum.php — Bitcoin address generation via Electrum RPC or static fallback

function get_new_btc_address(): string {
    // Try Electrum RPC (if running as daemon on server)
    try {
        $host = ELECTRUM_RPC_HOST;
        $port = ELECTRUM_RPC_PORT;
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            $payload = json_encode([
                'id'     => 1,
                'method' => 'getunusedaddress',
                'params' => [],
            ]);
            $ch = curl_init("http://{$host}:{$port}");
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_USERPWD        => ELECTRUM_RPC_USER . ':' . ELECTRUM_RPC_PASS,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response) {
                $data = json_decode($response, true);
                if (!empty($data['result'])) {
                    log_info('Electrum address generated: ' . $data['result']);
                    return $data['result'];
                }
            }
        }
    } catch (Throwable $e) {
        log_error('Electrum error: ' . $e->getMessage());
    }
    // Fallback — return static address from config
    log_info('Using static BTC address (Electrum unavailable)');
    return STATIC_BTC_ADDRESS;
}

// Check blockchain.info for received BTC on an address
function blockchain_check_address(string $address): array {
    $url = "https://blockchain.info/rawaddr/" . urlencode($address) . "?limit=5";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'GoldOSRS/1.0',
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200 || !$response) {
        return ['total_received' => 0, 'final_balance' => 0, 'txs' => []];
    }
    $data = json_decode($response, true);
    return [
        'total_received' => (int)($data['total_received'] ?? 0), // satoshis
        'final_balance'  => (int)($data['final_balance'] ?? 0),
        'txs'            => $data['txs'] ?? [],
    ];
}

// Convert satoshis to BTC
function satoshis_to_btc(int $satoshis): float {
    return $satoshis / 100000000;
}

// BTC price in USD (CoinGecko free API)
function get_btc_price_usd(): float {
    static $price = null;
    if ($price !== null) return $price;

    $cache_file = DATA_PATH . '/btc_price.json';
    // Cache for 5 minutes
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (!empty($cached['usd'])) {
            $price = (float)$cached['usd'];
            return $price;
        }
    }
    $ch = curl_init('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_USERAGENT      => 'GoldOSRS/1.0',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['bitcoin']['usd'])) {
            $price = (float)$data['bitcoin']['usd'];
            file_put_contents($cache_file, json_encode(['usd' => $price]));
            return $price;
        }
    }
    // Fallback price
    return 65000.0;
}

// Calculate BTC amount for a USD value
function usd_to_btc(float $usd): float {
    $btc_price = get_btc_price_usd();
    if ($btc_price <= 0) return 0;
    return round($usd / $btc_price, 8);
}
