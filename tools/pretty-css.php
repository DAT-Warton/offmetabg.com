<?php
// Simple CSS prettifier: inserts newlines after selectors and rules for easier reading.
// Usage: php tools/pretty-css.php input.css output.css
// Note: This is a lightweight formatter (not a full parser). Keep backups.

if ($argc < 3) {
    echo "Usage: php pretty-css.php input.css output.css\n";
    exit(1);
}
$input = $argv[1];
$output = $argv[2];
if (!file_exists($input)) {
    echo "Input file not found: $input\n";
    exit(1);
}
$css = file_get_contents($input);
// Normalize whitespace
$css = preg_replace('/\s+/', ' ', $css);
// Put a newline after each closing brace
$css = str_replace('}', "}\n\n", $css);
// Put a newline before each selector (after a closing brace)
$css = preg_replace('/\s*\{\s*/', " {\n    ", $css);
// Put semicolon+space to semicolon+newline for rule separation
$css = str_replace('; ', ";\n    ", $css);
// Tidy up: ensure rules end with semicolon newline already handled
// Add simple indentation for closing brace lines
$lines = explode("\n", $css);
$pretty = '';
$indent = 0;
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '') { $pretty .= "\n"; continue; }
    if (strpos($trim, '}') === 0) {
        $indent = max(0, $indent-1);
    }
    $pretty .= str_repeat('    ', $indent) . $trim . "\n";
    if (strpos($trim, '{') !== false && strpos($trim, '}') === false) {
        $indent++;
    }
}
file_put_contents($output, $pretty);
echo "Wrote prettified CSS to $output\n";
