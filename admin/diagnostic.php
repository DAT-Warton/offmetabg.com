<?php
/**
 * Admin System Diagnostic
 * Safe diagnostic page to check admin system status
 */

// Start output buffering to capture any errors
ob_start();

$output = [];
$output[] = "<style>body{font-family:system-ui,sans-serif;max-width:800px;margin:40px auto;padding:20px;}h1{color:#333;}h2{color:#666;margin-top:30px;}pre{background:#f5f5f5;padding:15px;border-radius:4px;overflow-x:auto;}.success{color:#28a745;}.error{color:#dc3545;}.warning{color:#ffc107;}</style>";
$output[] = "<h1>🔍 Admin System Diagnostic</h1>";

// Check 1: PHP Version
$output[] = "<h2>PHP Environment</h2>";
$output[] = "<pre>";
$output[] = "PHP Version: " . PHP_VERSION;
$output[] = "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
$output[] = "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown');
$output[] = "Script: " . __FILE__;
$output[] = "</pre>";

// Check 2: File paths
$output[] = "<h2>File System</h2>";
$output[] = "<pre>";

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}
$output[] = "CMS_ROOT: " . CMS_ROOT;

$requiredFiles = [
    'includes/functions.php',
    'includes/database.php',
    'includes/language.php',
    'includes/icons.php',
    'includes/env-loader.php',
    'admin/dashboard.php',
    'admin/index.php',
];

$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $path = CMS_ROOT . '/' . $file;
    $exists = file_exists($path);
    $allFilesExist = $allFilesExist && $exists;
    $status = $exists ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
    $output[] = "$status $file";
}
$output[] = "</pre>";

// Check 3: Try loading core files
$output[] = "<h2>Core File Loading</h2>";
$output[] = "<pre>";

$loadErrors = [];
$files = [
    'env-loader.php' => CMS_ROOT . '/includes/env-loader.php',
    'database.php' => CMS_ROOT . '/includes/database.php',
    'functions.php' => CMS_ROOT . '/includes/functions.php',
    'language.php' => CMS_ROOT . '/includes/language.php',
    'icons.php' => CMS_ROOT . '/includes/icons.php',
];

foreach ($files as $name => $path) {
    try {
        require_once $path;
        $output[] = '<span class="success">✓</span> ' . $name . ' loaded';
    } catch (Throwable $e) {
        $loadErrors[] = $name . ': ' . $e->getMessage();
        $output[] = '<span class="error">✗</span> ' . $name . ' failed: ' . htmlspecialchars($e->getMessage());
    }
}
$output[] = "</pre>";

// Check 4: Database
if (class_exists('Database')) {
    $output[] = "<h2>Database</h2>";
    $output[] = "<pre>";
    try {
        $db = Database::getInstance();
        $output[] = '<span class="success">✓</span> Database class initialized';
        $output[] = "Driver: " . $db->getDriver();
        
        if (function_exists('db_enabled')) {
            $enabled = db_enabled();
            $output[] = "DB Enabled: " . ($enabled ? '<span class="success">Yes</span>' : '<span class="warning">No (using JSON)</span>');
        }
    } catch (Throwable $e) {
        $output[] = '<span class="error">✗</span> Database error: ' . htmlspecialchars($e->getMessage());
    }
    $output[] = "</pre>";
}

// Check 5: Session
$output[] = "<h2>Session</h2>";
$output[] = "<pre>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $output[] = '<span class="success">✓</span> Session started';
    $output[] = "Session ID: " . session_id();
    $output[] = "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive');
} catch (Throwable $e) {
    $output[] = '<span class="error">✗</span> Session error: ' . htmlspecialchars($e->getMessage());
}
$output[] = "</pre>";

// Check 6: Key functions
$output[] = "<h2>Core Functions</h2>";
$output[] = "<pre>";
$functions = ['db_enabled', 'db_table', '__', 'icon_lock', 'icon_edit', 'sanitize', 'redirect'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        $output[] = '<span class="success">✓</span> ' . $func . '()';
    } else {
        $output[] = '<span class="error">✗</span> ' . $func . '() - NOT FOUND';
    }
}
$output[] = "</pre>";

// Summary
$output[] = "<h2>Summary</h2>";
if (empty($loadErrors) && $allFilesExist) {
    $output[] = '<p class="success">✅ All systems operational! The 500 error must be coming from somewhere else.</p>';
    $output[] = '<p><strong>Next steps:</strong></p>';
    $output[] = '<ul>';
    $output[] = '<li>Check nginx error logs: <code>tail -f /var/log/nginx/offmetabg-error.log</code></li>';
    $output[] = '<li>Check PHP-FPM error logs: <code>tail -f /var/log/php8.3-fpm.log</code></li>';
    $output[] = '<li>Try accessing <a href="index.php">admin/index.php</a> again</li>';
    $output[] = '</ul>';
} else {
    $output[] = '<p class="error">⚠️ Issues found. Please fix the errors above.</p>';
}

// Output everything
ob_end_clean();
echo implode("\n", $output);
