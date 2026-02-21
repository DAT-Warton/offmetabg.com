<?php
// Simple CSS builder/minifier
// Usage: php tools/build-css.php

$root = dirname(__DIR__);
$cssDir = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR;
$files = [
    'themes.css' => 'themes.min.css',
    'app.css' => 'app.min.css',
];

function minify_css($css) {
    // Remove comments
    $css = preg_replace('!/\*.*?\*/!s', '', $css);
    // Remove whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    // Remove space around symbols
    $css = preg_replace('/\s*([{};:,>~+\(\)])\s*/', '$1', $css);
    // Remove trailing semicolons in blocks
    $css = preg_replace('/;}/', '}', $css);
    // Trim
    return trim($css);
}

$errors = [];
foreach ($files as $src => $dst) {
    $in = $cssDir . $src;
    $out = $cssDir . $dst;
    if (!file_exists($in)) {
        $errors[] = "Missing source: $in";
        continue;
    }
    $css = file_get_contents($in);
    $min = minify_css($css);
    file_put_contents($out, $min);
    echo "Written: $out (", strlen($min), " bytes)\n";
}

if ($errors) {
    foreach ($errors as $e) echo "ERROR: $e\n";
    exit(1);
}

echo "Done.\n";
