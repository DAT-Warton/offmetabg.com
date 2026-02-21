<?php
// Simple CSS minifier: removes comments and collapses whitespace.
// Usage: php minify-css.php input.css output.css
if ($argc < 3) {
    echo "Usage: php minify-css.php input.css output.css\n";
    exit(1);
}
$in = $argv[1];
$out = $argv[2];
if (!file_exists($in)) {
    echo "Input file not found: $in\n";
    exit(1);
}
$css = file_get_contents($in);
// Remove /* comments */
$css = preg_replace('#/\*.*?\*/#s', '', $css);
// Remove whitespace around symbols
$css = preg_replace('/\s*([{};:,>+~])\s*/', '$1', $css);
// Collapse multiple spaces/newlines to single space
$css = preg_replace('/\s+/', ' ', $css);
// Remove space before !important
$css = str_replace(' ! important', '!important', $css);
$css = trim($css);
file_put_contents($out, $css);
echo "Wrote minified CSS to $out\n";
