<?php
/**
 * Remove JSON fallback blocks from functions.php
 * This script removes all "if (!db_enabled())" blocks
 */

$file = __DIR__ . '/includes/functions.php';
$content = file_get_contents($file);

// Pattern to match: if (!db_enabled()) { ... } followed by blank lines/ensure_db_schema
// We'll do this iteratively

$patterns = [
    // Pattern 1: Simple JSON fallback with return
    '/\s*if \(!db_enabled\(\)\) \{\s+return load_json\([^\)]+\);\s+\}\s+/s',
    
    // Pattern 2: JSON fallback with save operations (multi-line)
    '/\s*if \(!db_enabled\(\)\) \{[^}]+load_json[^}]+save_json[^}]+\}\s+/s',
    
    // Pattern 3: More complex blocks - match opening brace to closing brace
    '/\s*if \(!db_enabled\(\)\) \{(?:[^{}]|\{[^}]*\})*\}\s+(?=ensure_db_schema)/s',
];

$originalContent = $content;
$replacements = 0;

foreach ($patterns as $pattern) {
    $newContent = preg_replace($pattern, "\n    ", $content, -1, $count);
    if ($count > 0) {
        $content = $newContent;
        $replacements += $count;
        echo "Pattern matched and removed: $count times\n";
    }
}

if ($replacements > 0) {
    // Backup original file
    file_put_contents($file . '.backup', $originalContent);
    
    // Save modified content
    file_put_contents($file, $content);
    
    echo "\n✅ Removed $replacements JSON fallback blocks\n";
    echo "📦 Backup saved to: {$file}.backup\n";
    echo "✨ Database-only mode activated!\n";
} else {
    echo "No JSON fallback blocks found (or already removed)\n";
}
