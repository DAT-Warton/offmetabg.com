<?php
/**
 * OffMetaBG CMS - Enterprise Edition
 * Complete Website Management System for Shared Hosting
 *
 * Replaces WordPress with a lightweight, controllable CMS
 * Optimized for cPanel/Shared Hosting deployment
 */

define('CMS_ROOT', __DIR__);
define('CMS_VERSION', '1.0.0');
define('CMS_ENV', getenv('CMS_ENV') ?: 'production');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling
if (CMS_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Ensure directories exist
$dirs = [CMS_ROOT . '/storage', CMS_ROOT . '/uploads', CMS_ROOT . '/logs', CMS_ROOT . '/cache'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// Load core functions (database must be loaded before functions)
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/router.php';

// Get current page/route
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$path = trim($path, '/');

// Remove script name from path if present
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($script_dir !== '/' && strpos($path, $script_dir) === 0) {
    $path = substr($path, strlen($script_dir));
    $path = trim($path, '/');
}

// Route: Admin panel
if (strpos($path, 'admin') === 0) {
    require CMS_ROOT . '/admin/index.php';
    exit;
}

// Route: Static files
if (serves_static_file($path)) {
    exit;
}

// Route: API endpoints
if ($path === 'api' || strpos($path, 'api/') === 0) {
    require CMS_ROOT . '/api/handler.php';
    exit;
}

// Frontend routing
// Homepage
if (empty($path) || $path === '/') {
    ob_start();
    include CMS_ROOT . '/templates/home.php';
    echo ob_get_clean();
    exit;
}

// Blog listing
if ($path === 'blog') {
    ob_start();
    include CMS_ROOT . '/templates/blog.php';
    echo ob_get_clean();
    exit;
}

// Single blog post
if (strpos($path, 'blog/') === 0) {
    $slug = substr($path, 5);
    $post = get_post($slug);
    if ($post && ($post['status'] ?? 'published') === 'published') {
        ob_start();
        include CMS_ROOT . '/templates/post.php';
        echo ob_get_clean();
        exit;
    }
}

// Single product page
if (strpos($path, 'product/') === 0) {
    $productSlug = substr($path, 8);
    $products = get_products_data();
    
    // Find product by slug
    $product = null;
    foreach ($products as $prod) {
        if (($prod['slug'] ?? '') === $productSlug && ($prod['status'] ?? 'published') === 'published') {
            $product = $prod;
            break;
        }
    }
    
    if ($product) {
        ob_start();
        include CMS_ROOT . '/templates/product.php';
        echo ob_get_clean();
        exit;
    }
}

// Category page - products by category
if (strpos($path, 'category/') === 0) {
    $categorySlug = strtolower(substr($path, 9));
    $categories = get_categories_data();
    
    // Find category by slug
    $category = null;
    foreach ($categories as $cat) {
        $catSlug = strtolower($cat['slug'] ?? '');
        if ($catSlug === $categorySlug && ($cat['active'] ?? true)) {
            $category = $cat;
            break;
        }
    }
    
    if ($category) {
        ob_start();
        include CMS_ROOT . '/templates/category.php';
        echo ob_get_clean();
        exit;
    }
}

// Single page (must be checked after blog to avoid conflicts)
$page = get_page($path);
if ($page && ($page['status'] ?? 'published') === 'published') {
    ob_start();
    include CMS_ROOT . '/templates/page.php';
    echo ob_get_clean();
    exit;
}

// 404 - Page not found
header('HTTP/1.1 404 Not Found');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></title>
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/404.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="container">
        <h1>404</h1>
        <p>Page Not Found</p>
        <a href="/">Go Home</a>
    </div>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

