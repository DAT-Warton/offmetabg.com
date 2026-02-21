<?php
// Comprehensive non-destructive smoke tests for pages and assets
// Usage: php tests/smoke.php

date_default_timezone_set('UTC');
$base = getenv('SMOKE_BASE') ?: 'https://offmetabg.com';
echo "Smoke test run: " . date('c') . " against $base\n\n";

function http_request($url) {
    $result = [
        'http_code' => 0,
        'headers' => [],
        'body' => '',
        'error' => null,
        'time' => 0,
    ];

    // Preferred: PHP curl extension
    if (extension_loaded('curl')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        $start = microtime(true);
        $resp = curl_exec($ch);
        $end = microtime(true);
        if ($resp === false) {
            $result['error'] = curl_error($ch);
            curl_close($ch);
            return $result;
        }
        $info = curl_getinfo($ch);
        $result['http_code'] = $info['http_code'];
        $result['time'] = round($end - $start, 3);
        $header_size = $info['header_size'];
        $result['headers'] = explode("\r\n", substr($resp, 0, $header_size));
        $result['body'] = substr($resp, $header_size);
        curl_close($ch);
        return $result;
    }

    // Check allow_url_fopen and openssl
    $allow_fopen = ini_get('allow_url_fopen');
    $have_openssl = extension_loaded('openssl');
    if (!$allow_fopen) {
        $result['error'] = 'allow_url_fopen is disabled in php.ini';
    }
    if (!$have_openssl && stripos($url, 'https://') === 0) {
        $result['error'] = ($result['error'] ? $result['error'] . '; ' : '') . 'openssl extension missing for HTTPS';
    }

    // Try file_get_contents if allowed
    if ($allow_fopen && ($have_openssl || stripos($url, 'http://') === 0)) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "User-Agent: SmokeTest/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];
        $context = stream_context_create($opts);
        $start = microtime(true);
        $body = @file_get_contents($url, false, $context);
        $end = microtime(true);
        $result['time'] = round($end - $start, 3);
        if ($body !== false) {
            $result['body'] = $body;
            $result['headers'] = isset($http_response_header) ? $http_response_header : [];
            if (!empty($result['headers']) && preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $result['headers'][0], $m)) {
                $result['http_code'] = (int)$m[1];
            }
            return $result;
        }
        $result['error'] = error_get_last()['message'] ?? 'file_get_contents failed';
    }

    // Last resort: system curl binary
    if (function_exists('shell_exec')) {
        $cmd = 'curl -sS -D - -m 20 ' . escapeshellarg($url);
        $out = @shell_exec($cmd);
        if ($out !== null && $out !== '') {
            $parts = preg_split("/\r?\n\r?\n/s", $out, 2);
            if (count($parts) === 2) {
                $hdr = explode("\n", str_replace("\r", "", $parts[0]));
                $body = $parts[1];
                $result['headers'] = $hdr;
                $result['body'] = $body;
                if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $hdr[0], $m)) $result['http_code'] = (int)$m[1];
                return $result;
            }
        }
    }

    if (empty($result['error'])) $result['error'] = 'no available HTTP client (curl extension, allow_url_fopen, or system curl)';
    return $result;
}

// Discover root-level public php pages (top-level files)
$root = realpath(__DIR__ . '/../');
$publicPages = [];
foreach (glob($root . DIRECTORY_SEPARATOR . '*.php') as $f) {
    $name = basename($f);
    if (in_array($name, ['router.php', 'includes.php', 'env.php'])) continue;
    if ($name === 'index.php') {
        $publicPages[] = '/';
    } else {
        $publicPages[] = '/' . $name;
    }
}

// Add known routes and templates to check
$known = ['/auth.php', '/cart.php', '/profile.php', '/password-reset.php', '/activate.php', '/inquiries.php', '/wishlist.php'];
foreach ($known as $k) if (!in_array($k, $publicPages)) $publicPages[] = $k;

// Discover assets under assets/ (css, js, images, fonts)
$assetFiles = [];
$assetDir = $root . DIRECTORY_SEPARATOR . 'assets';
if (is_dir($assetDir)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetDir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $rel = str_replace($root, '', $file->getPathname());
        $rel = str_replace('\\', '/', $rel);
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|webp|woff2?|ttf|eot|map)$/i', $rel)) {
            $assetFiles[] = $rel;
        }
    }
}

// Prepare tests
$tests = [];
foreach ($publicPages as $p) {
    $url = rtrim($base, '/') . ($p === '/' ? '/' : $p);
    $tests[] = ['type' => 'page', 'path' => $p, 'url' => $url];
}
foreach ($assetFiles as $a) {
    $url = rtrim($base, '/') . $a;
    $tests[] = ['type' => 'asset', 'path' => $a, 'url' => $url];
}

// Run tests
$summary = ['total' => count($tests), 'ok' => 0, 'fail' => 0];
foreach ($tests as $t) {
    echo "Testing {$t['type']}: {$t['url']}\n";
    $res = http_request($t['url']);
    $code = $res['http_code'] ?? 0;
    echo "  HTTP: $code  time={$res['time']}s";
    if (!empty($res['error'])) echo "  ERR: {$res['error']}";
    echo "\n";
    $pass = false;
    if ($t['type'] === 'page') {
        // Only treat 2xx as success; 3xx/4xx/5xx are failures
        if ($code >= 200 && $code < 300) {
            $body = $res['body'] ?? '';
            if (preg_match('/<title[^>]*>.*<\/title>/is', $body) || strlen(strip_tags($body)) > 200) {
                $pass = true;
            }
        }
    } else {
        // assets: require 2xx and non-empty body
        if ($code >= 200 && $code < 300 && !empty($res['body'])) $pass = true;
    }
    if ($pass) {
        echo "  -> OK\n\n";
        $summary['ok']++;
    } else {
        echo "  -> FAIL\n\n";
        $summary['fail']++;
    }
}

echo "Summary: total={$summary['total']} ok={$summary['ok']} fail={$summary['fail']}\n";
if ($summary['fail'] > 0) exit(2);
exit(0);
