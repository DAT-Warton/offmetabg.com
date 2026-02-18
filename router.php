<?php
/**
 * PHP Built-in Server Router
 * Routes all requests through the CMS
 */

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Allow direct access to static files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    // Check if it's a static file (not PHP)
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    $static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'webp'];
    
    if (in_array(strtolower($ext), $static_extensions)) {
        return false; // Let PHP's built-in server serve the file
    }
}

// For admin URLs, route to admin/index.php
if (strpos($uri, '/admin') === 0 && file_exists(__DIR__ . '/admin/index.php')) {
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    require __DIR__ . '/admin/index.php';
    return true;
}

// For auth.php, serve it directly
if (strpos($uri, '/auth.php') !== false && file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
    return true;
}

// For activate.php and /activate/TOKEN URLs
if ((strpos($uri, '/activate.php') !== false || strpos($uri, '/activate/') === 0) && file_exists(__DIR__ . '/activate.php')) {
    require __DIR__ . '/activate.php';
    return true;
}

// For password-reset.php and /reset/TOKEN URLs
if ((strpos($uri, '/password-reset.php') !== false || strpos($uri, '/reset/') === 0) && file_exists(__DIR__ . '/password-reset.php')) {
    require __DIR__ . '/password-reset.php';
    return true;
}

// All other requests go through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
