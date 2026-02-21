<?php
/**
 * Currency Exchange Rate Manager
 * Integrates with Bulgarian National Bank (BNB) API for real-time EUR/BGN rates
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

/**
 * Get EUR to BGN exchange rate from BNB
 * Cache for 24 hours to avoid API rate limits
 * 
 * @return float Exchange rate (e.g., 1.9558 means 1 EUR = 1.9558 BGN)
 */
function get_eur_bgn_rate() {
    $cache_file = CMS_ROOT . '/cache/eur_bgn_rate.json';
    $cache_ttl = 86400; // 24 hours (BNB updates daily)
    
    // Check cache first
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if (isset($cache_data['rate']) && isset($cache_data['timestamp'])) {
            $age = time() - $cache_data['timestamp'];
            if ($age < $cache_ttl) {
                return floatval($cache_data['rate']);
            }
        }
    }
    
    // Fetch from BNB API
    try {
        // BNB provides fixed rate since Bulgaria uses currency board (1 EUR = 1.95583 BGN)
        // But we'll use their official API for legal compliance
        $xml_url = 'https://www.bnb.bg/Statistics/StExternalSector/StExchangeRates/StERForeignCurrencies/index.htm?download=xml&search=&lang=BG';
        
        // Alternative: Use fixed rate as fallback (official currency board rate)
        $fixed_rate = 1.95583; // Official EUR/BGN fixed rate since 1997
        
        // Try to fetch from BNB (optional - can fail gracefully)
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'OffmetaBG/1.0'
            ]
        ]);
        
        $xml_content = @file_get_contents($xml_url, false, $context);
        
        if ($xml_content) {
            $xml = @simplexml_load_string($xml_content);
            if ($xml && isset($xml->ROW)) {
                foreach ($xml->ROW as $row) {
                    if (isset($row['GOLD']) && (string)$row['GOLD'] === '1') {
                        // EUR is marked with GOLD="1" in BNB XML
                        $rate = floatval((string)$row['RATE']);
                        if ($rate > 0) {
                            $fixed_rate = $rate;
                            break;
                        }
                    }
                }
            }
        }
        
        // Cache the result
        $cache_data = [
            'rate' => $fixed_rate,
            'timestamp' => time(),
            'source' => $xml_content ? 'BNB API' : 'Fixed rate (currency board)'
        ];
        
        @file_put_contents($cache_file, json_encode($cache_data));
        
        return $fixed_rate;
        
    } catch (Exception $e) {
        error_log('Exchange rate fetch failed: ' . $e->getMessage());
        // Return official fixed rate as fallback
        return 1.95583;
    }
}

/**
 * Convert EUR to BGN
 * 
 * @param float $eur_amount Amount in EUR
 * @return float Amount in BGN
 */
function convert_eur_to_bgn($eur_amount) {
    $rate = get_eur_bgn_rate();
    return $eur_amount * $rate;
}

/**
 * Convert BGN to EUR
 * 
 * @param float $bgn_amount Amount in BGN
 * @return float Amount in EUR
 */
function convert_bgn_to_eur($bgn_amount) {
    $rate = get_eur_bgn_rate();
    return $bgn_amount / $rate;
}

/**
 * Format price with dual currency display
 * Shows both EUR and BGN with real-time conversion
 * 
 * @param float $price Base price (in EUR)
 * @param string $base_currency Base currency code (EUR or BGN)
 * @param bool $show_both Show both currencies
 * @return array ['eur' => float, 'bgn' => float, 'rate' => float]
 */
function get_dual_currency_price($price, $base_currency = 'EUR', $show_both = true) {
    $rate = get_eur_bgn_rate();
    
    if ($base_currency === 'EUR') {
        $eur = $price;
        $bgn = $price * $rate;
    } else {
        $bgn = $price;
        $eur = $price / $rate;
    }
    
    return [
        'eur' => round($eur, 2),
        'bgn' => round($bgn, 2),
        'rate' => $rate,
        'base_currency' => $base_currency
    ];
}

/**
 * Format price display HTML with dual currencies
 * 
 * @param float $price Price in EUR
 * @param bool $show_bgn Show BGN conversion
 * @param string $css_class Additional CSS class
 * @return string HTML formatted price
 */
function format_dual_price($price, $show_bgn = true, $css_class = '') {
    if ($price <= 0) {
        return '<span class="contact-price ' . htmlspecialchars($css_class) . '">📞 Свържете се за цена</span>';
    }
    
    $dual = get_dual_currency_price($price, 'EUR');
    
    $html = '<span class="price-display ' . htmlspecialchars($css_class) . '">';
    $html .= '<span class="price-eur" style="font-weight: 700; font-size: 1.1em;">';
    $html .= number_format($dual['eur'], 2) . ' €';
    $html .= '</span>';
    
    if ($show_bgn) {
        $html .= ' <span class="price-bgn" style="color: #666; font-size: 0.9em; margin-left: 6px;">';
        $html .= '(' . number_format($dual['bgn'], 2) . ' лв.)';
        $html .= '</span>';
    }
    
    $html .= '</span>';
    
    return $html;
}

/**
 * Get exchange rate update info
 * 
 * @return array Info about last rate update
 */
function get_exchange_rate_info() {
    $cache_file = CMS_ROOT . '/cache/eur_bgn_rate.json';
    
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        return [
            'rate' => $cache_data['rate'] ?? 1.95583,
            'last_update' => $cache_data['timestamp'] ?? 0,
            'source' => $cache_data['source'] ?? 'Unknown',
            'age_hours' => isset($cache_data['timestamp']) ? round((time() - $cache_data['timestamp']) / 3600, 1) : 0
        ];
    }
    
    return [
        'rate' => 1.95583,
        'last_update' => 0,
        'source' => 'Not fetched yet',
        'age_hours' => 0
    ];
}

/**
 * Force refresh exchange rate from BNB
 * 
 * @return bool Success
 */
function refresh_exchange_rate() {
    $cache_file = CMS_ROOT . '/cache/eur_bgn_rate.json';
    @unlink($cache_file);
    get_eur_bgn_rate(); // This will fetch fresh rate
    return true;
}
