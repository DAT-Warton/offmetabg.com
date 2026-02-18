<?php
/**
 * Core CMS Functions
 */

// Load environment variables early
require_once __DIR__ . '/env-loader.php';

// Load site settings helper
require_once __DIR__ . '/site-settings.php';

// Get current user
function current_user() {
    return $_SESSION['user'] ?? null;
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// Require admin
function require_admin() {
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied');
    }
}

// Get CMS setting
function get_option($key, $default = null) {
    $pdo = Database::getInstance()->getPDO();
    try {
        $stmt = $pdo->prepare('SELECT option_value FROM options WHERE option_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        error_log("Error getting option {$key}: ". $e->getMessage());
        return $default;
    }
}

// Update CMS setting
function update_option($key, $value) {
    $pdo = Database::getInstance()->getPDO();
    try {
        $stmt = $pdo->prepare('INSERT INTO options (option_key, option_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (option_key) DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([$key, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating option {$key}: ". $e->getMessage());
        return false;
    }
}

// Load JSON file
function load_json($file) {
    $path = CMS_ROOT . '/' . $file;
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    return json_decode($content, true) ?? [];
}

// Save JSON file
function save_json($file, $data) {
    $path = CMS_ROOT . '/' . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

function db_enabled() {
    // Database is now always required - no JSON fallback
    if (!class_exists('Database')) {
        throw new Exception('Database class not found. Check database.php is loaded.');
    }
    $db = Database::getInstance();
    if (!$db->getPDO()) {
        throw new Exception('Database connection failed. Check your configuration.');
    }
    return true;
}

function db_table($name) {
    return Database::getInstance()->table($name);
}

function db_get_option($key, $default = null) {
    if (!class_exists('Database')) {
        return $default;
    }
    return Database::getInstance()->getOption($key, $default);
}

function db_set_option($key, $value) {
    if (!class_exists('Database')) {
        return false;
    }
    return Database::getInstance()->setOption($key, $value);
}

/**
 * Get custom theme CSS variables for inline injection
 * Returns inline style tag with CSS variables for custom themes
 */
function get_custom_theme_css() {
    $activeTheme = db_get_option('active_theme', 'default');
    
    // Built-in themes don't need inline CSS
    $builtInThemes = ['default', 'dark', 'ocean', 'forest', 'sunset', 'rose'];
    if (in_array($activeTheme, $builtInThemes)) {
        return '';
    }
    
    // Load custom theme from database
    try {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT variables FROM themes WHERE slug = ? LIMIT 1");
            $stmt->execute([$activeTheme]);
            $theme = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($theme && !empty($theme['variables'])) {
                // Parse JSON variables
                $variables = is_string($theme['variables']) 
                    ? json_decode($theme['variables'], true) 
                    : $theme['variables'];
                
                if ($variables && is_array($variables)) {
                    // Generate CSS variables
                    $cssVars = [];
                    foreach ($variables as $varName => $value) {
                        $cssVars[] = "   --{$varName}: {$value};";
                    }
                    
                    $css = ":root[data-theme=\"{$activeTheme}\"] {\n". implode("\n", $cssVars) . "\n}";
                    
                    return "<style id=\"custom-theme-vars\">\n{$css}\n</style>";
                }
            }
        }
    } catch (Exception $e) {
        error_log("Failed to load custom theme CSS: ". $e->getMessage());
    }
    
    return '';
}

function ensure_db_schema() {
    static $done = false;

    if ($done || !db_enabled()) {
        return;
    }

    $pdo = Database::getInstance()->getPDO();
    $alterStatements = [
        "ALTER TABLE customers ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'customer'",
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS permissions TEXT',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS activated BOOLEAN DEFAULT false',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS activated_at TIMESTAMP',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS activation_token_expires TIMESTAMP',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT false',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS activation_token VARCHAR(255)',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255)',
        'ALTER TABLE customers ADD COLUMN IF NOT EXISTS reset_expires TIMESTAMP',
        'ALTER TABLE categories ADD COLUMN IF NOT EXISTS icon TEXT',
        'ALTER TABLE categories ADD COLUMN IF NOT EXISTS display_order INTEGER DEFAULT 0',
        'ALTER TABLE categories ADD COLUMN IF NOT EXISTS active BOOLEAN DEFAULT true',
        'ALTER TABLE categories ADD COLUMN IF NOT EXISTS product_count INTEGER DEFAULT 0',
        'ALTER TABLE promotions ADD COLUMN IF NOT EXISTS data TEXT',
        'ALTER TABLE discounts ADD COLUMN IF NOT EXISTS first_purchase_only BOOLEAN DEFAULT false',
    ];

    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Ignore schema update errors to avoid breaking requests.
        }
    }

    $done = true;
}

function parse_json_field($value) {
    if ($value === null || $value === '') {
        return null;
    }
    $decoded = json_decode($value, true);
    return $decoded === null ? $value : $decoded;
}

/**
 * Clear cached product data for all languages
 * Called when products are added, updated, or deleted
 */
function clear_products_cache() {
    $cache_dir = CMS_ROOT . '/cache';
    if (!is_dir($cache_dir)) {
        return;
    }
    
    // Clear product cache for both languages
    $cache_files = [
        $cache_dir . '/products_data_bg.json',
        $cache_dir . '/products_data_en.json'
    ];
    
    foreach ($cache_files as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

// Get all pages
function get_pages() {
    $rows = db_table('pages')->all();
    $pages = [];
    foreach ($rows as $row) {
        $slug = $row['slug'] ?? '';
        if ($slug === '') {
            continue;
        }
        $pages[$slug] = [
            'id' => $row['id'] ?? $slug,
            'slug' => $slug,
            'title' => $row['title'] ?? '',
            'content' => $row['content'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'status' => $row['status'] ?? 'published',
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? '',
        ];
    }
    return $pages;
}

// Get page by slug
function get_page($slug) {
    if (db_enabled()) {
        $row = db_table('pages')->find('slug', $slug);
        if (!$row) {
            return null;
        }
        return [
            'id' => $row['id'] ?? $slug,
            'slug' => $row['slug'] ?? $slug,
            'title' => $row['title'] ?? '',
            'content' => $row['content'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'status' => $row['status'] ?? 'published',
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? '',
        ];
    }

    $pages = get_pages();
    return $pages[$slug] ?? null;
}

// Save page
function save_page($slug, $data) {
    $originalSlug = trim((string)$slug);
    $slug = $originalSlug;
    if ($slug === '') {
        $slug = generate_slug($data['title'] ?? '');
    }
    if ($slug === '') {
        $slug = 'page-' . substr(uniqid('', true), -6);
    }

    $table = db_table('pages');
    if ($originalSlug === '') {
        $baseSlug = $slug;
        $counter = 2;
        while ($table->find('slug', $slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    $existing = $table->find('slug', $slug);
    $payload = [
        'slug' => $slug,
        'title' => $data['title'] ?? '',
        'content' => $data['content'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'status' => $data['status'] ?? 'published',
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($existing) {
        $table->update($existing['id'], $payload);
        return true;
    }

    $payload['id'] = uniqid('page_');
    $payload['created_at'] = date('Y-m-d H:i:s');
    $table->insert($payload);
    return true;
}

// Delete page
function delete_page($slug) {
    $table = db_table('pages');
    $existing = $table->find('slug', $slug);
    if ($existing) {
        $table->delete($existing['id']);
    }
    return true;
}

// Get all posts
function get_posts() {
    $rows = db_table('posts')->all();
    $posts = [];
    foreach ($rows as $row) {
        $slug = $row['slug'] ?? '';
        if ($slug === '') {
            continue;
        }
        $posts[$slug] = [
            'id' => $row['id'] ?? $slug,
            'slug' => $slug,
            'title' => $row['title'] ?? '',
            'content' => $row['content'] ?? '',
            'excerpt' => $row['excerpt'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'featured_image' => $row['featured_image'] ?? '',
            'category' => $row['category'] ?? 'uncategorized',
            'status' => $row['status'] ?? 'published',
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? '',
        ];
    }
    return $posts;
}

// Get post by slug
function get_post($slug) {
    if (db_enabled()) {
        $row = db_table('posts')->find('slug', $slug);
        if (!$row) {
            return null;
        }
        return [
            'id' => $row['id'] ?? $slug,
            'slug' => $row['slug'] ?? $slug,
            'title' => $row['title'] ?? '',
            'content' => $row['content'] ?? '',
            'excerpt' => $row['excerpt'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'featured_image' => $row['featured_image'] ?? '',
            'category' => $row['category'] ?? 'uncategorized',
            'status' => $row['status'] ?? 'published',
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? '',
        ];
    }

    $posts = get_posts();
    return $posts[$slug] ?? null;
}

// Save post
function save_post($slug, $data) {
    // Generate slug from title if empty
    if (empty($slug) && !empty($data['title'])) {
        $slug = generate_slug($data['title']);
    }
    
    // Parse custom datetime if provided (DD/MM/YYYY HH:MM)
    $custom_datetime = null;
    if (!empty($data['created_datetime'])) {
        $dt = DateTime::createFromFormat('d/m/Y H:i', $data['created_datetime']);
        if ($dt) {
            $custom_datetime = $dt->format('Y-m-d H:i:s');
        }
    }
    
    $table = db_table('posts');
    $existing = $table->find('slug', $slug);
    $payload = [
        'slug' => $slug,
        'title' => $data['title'] ?? '',
        'content' => $data['content'] ?? '',
        'excerpt' => $data['excerpt'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'featured_image' => $data['featured_image'] ?? '',
        'status' => $data['status'] ?? 'published',
        'category' => $data['category'] ?? 'uncategorized',
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($existing) {
        // Only update created_at if custom_datetime provided and different
        if ($custom_datetime && $custom_datetime !== $existing['created_at']) {
            $payload['created_at'] = $custom_datetime;
        }
        $table->update($existing['id'], $payload);
        return $slug;
    }

    $payload['id'] = uniqid('post_');
    $payload['created_at'] = $custom_datetime ?? date('Y-m-d H:i:s');
    $table->insert($payload);
    return $slug;
}

// Delete post
function delete_post($slug) {
    $table = db_table('posts');
    $existing = $table->find('slug', $slug);
    if ($existing) {
        $table->delete($existing['id']);
    }
    return true;
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Render safe HTML for product descriptions
function render_description($description) {
    if (empty($description)) {
        return '';
    }
    
    // Replace escaped newlines with actual newlines
    $description = str_replace('\n', "\n", $description);
    
    // Allow only safe HTML tags
    $allowed_tags = '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div><a>';
    $description = strip_tags($description, $allowed_tags);
    
    // Convert newlines to <br> if not already in HTML format
    if (strpos($description, '<') === false) {
        $description = nl2br($description);
    }
    
    return $description;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate slug from title
function generate_slug($title) {
    $slug = trim($title);
    if (function_exists('mb_strtolower')) {
        $slug = mb_strtolower($slug, 'UTF-8');
    } else {
        $slug = strtolower($slug);
    }
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Redirect
function redirect($path) {
    // For empty path or root, redirect to home
    if (empty($path) || $path === '/') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        header('Location: ' . $protocol . '://' . $host . '/');
        exit;
    }
    
    // If path already contains .php or query string, use as-is
    if (strpos($path, '.php') !== false || strpos($path, '?') !== false) {
        header('Location: ' . $path);
        exit;
    }
    
    // Otherwise, construct path
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $base . '/' . ltrim($path, '/'));
    exit;
}

// Get URL
function url($path = '') {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $base . '/' . ltrim($path, '/');
}

// Serve static file
function serves_static_file($path) {
    $extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
    $ext = pathinfo($path, PATHINFO_EXTENSION);

    if (!in_array(strtolower($ext), $extensions)) {
        return false;
    }

    $file = CMS_ROOT . '/public/' . $path;
    if (!file_exists($file) || !is_file($file)) {
        return false;
    }

    // Set cache headers
    header('Cache-Control: public, max-age=31536000');

    // Set content type
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];

    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    readfile($file);
    return true;
}

// Render template
function render($template, $data = []) {
    extract($data);
    $file = CMS_ROOT . '/templates/' . $template . '.php';
    if (file_exists($file)) {
        ob_start();
        require $file;
        return ob_get_clean();
    }
    return '';
}

// Get stats for dashboard - OPTIMIZED with caching
function get_dashboard_stats() {
    // Use caching system if available
    if (file_exists(__DIR__ . '/dashboard-cache.php')) {
        require_once __DIR__ . '/dashboard-cache.php';
        $cache = new DashboardCache();
        return $cache->remember('basic_dashboard_stats', function() {
            return get_dashboard_stats_internal();
        }, 300); // 5 minutes cache
    }
    
    // Fallback to direct execution
    return get_dashboard_stats_internal();
}

// Internal function - actual stats calculation
function get_dashboard_stats_internal() {
    $totalUsers = 0;
    try {
        $customers = db_table('customers')->all();
        $totalUsers = count($customers);
    } catch (Exception $e) {
        // If table doesn't exist yet, return 0
    }
    
    return [
        'total_pages' => count(get_pages()),
        'total_posts' => count(get_posts()),
        'total_users' => $totalUsers,
        'site_views' => get_option('site_views', 0),
    ];
}

/**
 * Social Media Video Embed Functions
 */

// Extract YouTube video ID from URL
function get_youtube_id($url) {
    if (empty($url)) return null;
    
    $patterns = [
        '/youtube\.com\/watch\?v=([^&]+)/',
        '/youtu\.be\/([^&?]+)/',
        '/youtube\.com\/embed\/([^&?]+)/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Generate YouTube embed HTML
function get_youtube_embed($url, $width = '100%', $height = '400') {
    $videoId = get_youtube_id($url);
    if (!$videoId) return '';
    
    return '<div class="video-container"style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 8px; margin-bottom: 20px;">
        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
            src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>';
}

// Extract TikTok video ID from URL
function get_tiktok_id($url) {
    if (empty($url)) {
        return null;
    }
    
    if (preg_match('/tiktok\.com\/@[^\/]+\/video\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Generate TikTok embed HTML
function get_tiktok_embed($url) {
    $videoId = get_tiktok_id($url);
    if (!$videoId) return '';
    
    return '<div class="video-container"style="max-width: 605px; margin: 0 auto 20px; background: #000; border-radius: 8px; overflow: hidden;">
        <blockquote class="tiktok-embed"cite="' . htmlspecialchars($url) . '"data-video-id="' . htmlspecialchars($videoId) . '"style="max-width: 605px; min-width: 325px;">
            <section></section>
        </blockquote>
        <script async src="https://www.tiktok.com/embed.js"></script>
    </div>';
}

// Extract Instagram post ID from URL
function get_instagram_id($url) {
    if (empty($url)) {
        return null;
    }
    
    if (preg_match('/instagram\.com\/(p|reel)\/([^\/\?]+)/', $url, $matches)) {
        return $matches[2];
    }
    return null;
}

// Generate Instagram embed HTML
function get_instagram_embed($url) {
    $postId = get_instagram_id($url);
    if (!$postId) {
        return '';
    }
    
    $embedUrl = 'https://www.instagram.com/p/' . $postId . '/embed';
    
    return '<div class="video-container"style="max-width: 540px; margin: 0 auto 20px; background: #fff; border: 1px solid #dbdbdb; border-radius: 3px; overflow: hidden;">
        <iframe src="' . htmlspecialchars($embedUrl) . '"
            width="540"
            height="840"
            frameborder="0"
            scrolling="no"
            allowtransparency="true"
            style="border: none; overflow: hidden; width: 100%; max-width: 540px;">
        </iframe>
    </div>';
}

// Generate all video embeds for a product
function get_product_videos_html($product) {
    if (empty($product['videos'])) return '';
    
    $html = '';
    $videos = $product['videos'];
    
    if (!empty($videos['youtube'])) {
        $html .= get_youtube_embed($videos['youtube']);
    }
    
    if (!empty($videos['tiktok'])) {
        $html .= get_tiktok_embed($videos['tiktok']);
    }
    
    if (!empty($videos['instagram'])) {
        $html .= get_instagram_embed($videos['instagram']);
    }
    
    return $html;
}

function get_products_data() {
    // Get current language or default to 'bg'
    $lang = $_SESSION['lang'] ?? 'bg';
    
    // Check cache first (5 minute TTL for massive performance boost)
    $cache_key = 'products_data_' . $lang;
    $cache_file = CMS_ROOT . '/cache/' . $cache_key . '.json';
    $cache_ttl = 300; // 5 minutes
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached_data = file_get_contents($cache_file);
        $products = json_decode($cached_data, true);
        if ($products !== null) {
            return $products;
        }
    }
    
    $pdo = Database::getInstance()->getPDO();
    
    // Query with JOINs to get all product data from normalized tables
    $query = "
        SELECT 
            p.id,
            p.sku,
            p.slug,
            p.status,
            p.featured,
            p.created_at,
            p.updated_at,
            pd.name,
            pd.short_description,
            pd.description,
            pp.price,
            pp.compare_price,
            pp.currency,
            pi.image_url,
            pv.video_url,
            pv.platform as video_platform,
            pinv.quantity as stock,
            c.slug as category_slug,
            c.name as category_name
        FROM products p
        LEFT JOIN product_descriptions pd ON p.id = pd.product_id AND pd.language_code = ?
        LEFT JOIN product_prices pp ON p.id = pp.product_id AND pp.is_active = true
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.image_type = 'primary'
        LEFT JOIN product_videos pv ON p.id = pv.product_id AND pv.sort_order = 0
        LEFT JOIN product_inventory pinv ON p.id = pinv.product_id
        LEFT JOIN product_category_links pcl ON p.id = pcl.product_id
        LEFT JOIN categories c ON pcl.category_id = c.id
        ORDER BY p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$lang]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $products = [];
    
    foreach ($rows as $row) {
        // Parse image_url if it's JSON
        $imageData = $row['image_url'] ? json_decode($row['image_url'], true) : null;
        $image = '';
        $videos = ['youtube' => '', 'tiktok' => '', 'instagram' => ''];
        
        if (is_array($imageData)) {
            $image = $imageData['primary'] ?? '';
            if (isset($imageData['videos']) && is_array($imageData['videos'])) {
                $videos = array_merge($videos, $imageData['videos']);
            }
        } elseif (is_string($imageData)) {
            $image = $imageData;
        } elseif (is_string($row['image_url'])) {
            $image = $row['image_url'];
        }
        
        // Add video from product_videos table if available
        if (!empty($row['video_platform']) && !empty($row['video_url'])) {
            $videos[$row['video_platform']] = $row['video_url'];
        }
        
        $products[$row['id']] = [
            'id' => $row['id'],
            'sku' => $row['sku'] ?? '',
            'slug' => $row['slug'] ?? '',
            'name' => $row['name'] ?? '',
            'description' => $row['description'] ?? $row['short_description'] ?? '',
            'short_description' => $row['short_description'] ?? '',
            'price' => floatval($row['price'] ?? 0),
            'compare_price' => floatval($row['compare_price'] ?? 0),
            'currency' => $row['currency'] ?? 'BGN',
            'image' => $image,
            'category' => $row['category_slug'] ?? $row['category_name'] ?? '',
            'stock' => intval($row['stock'] ?? 0),
            'status' => $row['status'] ?? 'published',
            'featured' => (bool)($row['featured'] ?? false),
            'videos' => $videos,
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }

    // Cache the results for next request (5 min TTL = 75% faster page loads)
    file_put_contents($cache_file, json_encode($products, JSON_UNESCAPED_UNICODE));

    return $products;
}

function save_product_data($data) {
    $pdo = Database::getInstance()->getPDO();
    $product_id = $data['id'] ?: uniqid('prod_');
    $lang = $_SESSION['lang'] ?? 'bg';
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert/Update main product record
        $stmt = $pdo->prepare("
            INSERT INTO products (id, sku, slug, status, featured, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO UPDATE SET
                sku = EXCLUDED.sku,
                slug = EXCLUDED.slug,
                status = EXCLUDED.status,
                featured = EXCLUDED.featured,
                updated_at = CURRENT_TIMESTAMP
        ");
        $featured_value = !empty($data['featured']) ? 'true' : 'false';
        $stmt->execute([
            $product_id,
            $data['sku'] ?? '',
            $data['slug'] ?? generate_slug($data['name'] ?? $product_id),
            $data['status'] ?? 'published',
            $featured_value
        ]);
        
        // 2. Insert/Update product description
        $stmt = $pdo->prepare("
            INSERT INTO product_descriptions (product_id, language_code, name, short_description, description)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (product_id, language_code) DO UPDATE SET
                name = EXCLUDED.name,
                short_description = EXCLUDED.short_description,
                description = EXCLUDED.description
        ");
        $stmt->execute([
            $product_id,
            $lang,
            $data['name'] ?? '',
            $data['short_description'] ?? '',
            $data['description'] ?? ''
        ]);
        
        // 3. Insert/Update product price - first deactivate old prices, then insert new
        $stmt = $pdo->prepare("UPDATE product_prices SET is_active = false WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        $stmt = $pdo->prepare("
            INSERT INTO product_prices (product_id, price, compare_price, currency, is_active, valid_from)
            VALUES (?, ?, ?, ?, true, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $product_id,
            floatval($data['price'] ?? 0),
            floatval($data['compare_price'] ?? 0),
            $data['currency'] ?? 'BGN'
        ]);
        
        // 4. Insert/Update product image
        if (!empty($data['image'])) {
            $videos = $data['videos'] ?? [];
            $imageData = json_encode([
                'primary' => $data['image'],
                'videos' => $videos
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("
                INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                VALUES (?, ?, 'primary', 0)
                ON CONFLICT (product_id, image_type) DO UPDATE SET
                    image_url = EXCLUDED.image_url
            ");
            $stmt->execute([$product_id, $imageData]);
        }
        
        // 5. Insert/Update product inventory
        $stmt = $pdo->prepare("
            INSERT INTO product_inventory (product_id, quantity)
            VALUES (?, ?)
            ON CONFLICT (product_id) DO UPDATE SET
                quantity = EXCLUDED.quantity
        ");
        $stmt->execute([
            $product_id,
            intval($data['stock'] ?? 0)
        ]);
        
        // 6. Link to category if provided
        if (!empty($data['category'])) {
            // Find category by slug or name
            $stmt = $pdo->prepare("
                SELECT id FROM categories 
                WHERE slug = :slug OR LOWER(name) = LOWER(:name)
                LIMIT 1
            ");
            $stmt->execute([
                ':slug' => $data['category'],
                ':name' => $data['category']
            ]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                // First delete existing links for this product
                $stmt = $pdo->prepare("
                    DELETE FROM product_category_links 
                    WHERE product_id = :product_id
                ");
                $stmt->execute([':product_id' => $product_id]);
                
                // Then insert new link
                $stmt = $pdo->prepare("
                    INSERT INTO product_category_links (product_id, category_id, is_primary)
                    VALUES (:product_id, :category_id, :is_primary)
                ");
                $stmt->execute([
                    ':product_id' => $product_id,
                    ':category_id' => $category['id'],
                    ':is_primary' => true
                ]);
            } else {
                error_log("Warning: Category '{$data['category']}' not found for product {$product_id}");
            }
        }
        
        $pdo->commit();
        update_category_product_counts();
        
        // Clear product cache for all languages
        clear_products_cache();
        
        return $product_id;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error saving product [ID: {$product_id}]: ". $e->getMessage() . "| File: ". $e->getFile() . "| Line: ". $e->getLine());
        throw $e;
    }
}

function delete_product_data($product_id) {
    $pdo = Database::getInstance()->getPDO();
    
    try {
        // Cascade delete will handle related records due to ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        update_category_product_counts();
        
        // Clear product cache for all languages
        clear_products_cache();
        
        return true;
    } catch (Exception $e) {
        error_log("Error deleting product: ". $e->getMessage());
        throw $e;
    }
}

function get_categories_data() {
    $rows = db_table('categories')->all();
    $products = get_products_data();
    $productCounts = [];

    foreach ($products as $product) {
        $categoryKey = strtolower(trim($product['category'] ?? ''));
        if ($categoryKey === '') {
            continue;
        }
        if (!isset($productCounts[$categoryKey])) {
            $productCounts[$categoryKey] = 0;
        }
        $productCounts[$categoryKey]++;
    }

    $categories = [];
    foreach ($rows as $row) {
        $imageData = parse_json_field($row['image'] ?? null);
        $icon = $row['icon'] ?? '';
        if ($icon === '' && is_array($imageData) && isset($imageData['icon'])) {
            $icon = $imageData['icon'];
        }

        $slug = $row['slug'] ?? '';
        $order = isset($row['display_order']) ? intval($row['display_order']) : intval($row['sort_order'] ?? 0);
        $active = isset($row['active']) ? (bool)$row['active'] : (($row['status'] ?? 'active') === 'active');
        $categoryKey = strtolower(trim($slug));
        $productCount = $productCounts[$categoryKey] ?? intval($row['product_count'] ?? 0);

        $categories[$row['id']] = [
            'id' => $row['id'],
            'name' => $row['name'] ?? '',
            'slug' => $slug,
            'description' => $row['description'] ?? '',
            'parent_id' => $row['parent_id'] ?? '',
            'icon' => $icon,
            'order' => $order,
            'active' => $active,
            'product_count' => $productCount,
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }

    return $categories;
}

function save_category_data($data) {
    $table = db_table('categories');
    $category_id = $data['id'] ?: uniqid('cat_');
    $imagePayload = json_encode([
        'icon' => $data['icon'] ?? '',
        'image' => $data['image'] ?? ''
    ], JSON_UNESCAPED_UNICODE);

    $payload = [
        'slug' => $data['slug'] ?? '',
        'name' => $data['name'] ?? '',
        'description' => $data['description'] ?? '',
        'parent_id' => $data['parent_id'] ?? '',
        'image' => $imagePayload,
        'sort_order' => intval($data['order'] ?? 0),
        'display_order' => intval($data['order'] ?? 0),
        'status' => !empty($data['active']) ? 'active' : 'inactive',
        'active' => !empty($data['active']),
        'icon' => $data['icon'] ?? '',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $category_id);
    if ($existing) {
        $table->update($category_id, $payload);
    } else {
        $payload['id'] = $category_id;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    update_category_product_counts();
    return $category_id;
}

function delete_category_data($category_id) {
    db_table('categories')->delete($category_id);
    update_category_product_counts();
    return true;
}

function get_customers_data() {
    $customers = [];
    
    // Get customers from customers table
    $rows = db_table('customers')->all();
    foreach ($rows as $row) {
        $permissions = parse_json_field($row['permissions'] ?? null);
        $customers[$row['id']] = [
            'id' => $row['id'],
            'username' => $row['username'] ?? '',
            'email' => $row['email'] ?? '',
            'password' => $row['password'] ?? '',
            'role' => $row['role'] ?? 'customer',
            'permissions' => is_array($permissions) ? $permissions : [],
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }
    
    // Get admins from admins table
    $adminRows = db_table('admins')->all();
    foreach ($adminRows as $row) {
        $customers[$row['id']] = [
            'id' => $row['id'],
            'username' => $row['username'] ?? '',
            'email' => $row['email'] ?? '',
            'password' => $row['password'] ?? '',
            'role' => 'admin',
            'permissions' => ['full_access'],
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }

    return $customers;
}

function save_customer_data($data) {
    $role = $data['role'] ?? 'customer';
    
    // Determine which table to use based on role
    if ($role === 'admin') {
        $table = db_table('admins');
        $customer_id = $data['id'] ?: uniqid('admin_');
    } else {
        $table = db_table('customers');
        $customer_id = $data['id'] ?: uniqid('cust_');
    }
    
    $existing = $table->find('id', $customer_id);
    $password = $data['password'] ?? '';
    if ($existing && $password === '') {
        $password = $existing['password'] ?? '';
    }

    if ($role === 'admin') {
        // Admins table has simpler schema
        $payload = [
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $password,
            'role' => 'admin',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    } else {
        // Customers table has more fields
        $permissions = json_encode($data['permissions'] ?? ['view_products', 'place_orders'], JSON_UNESCAPED_UNICODE);
        $payload = [
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $password,
            'role' => 'customer',
            'permissions' => $permissions,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    if ($existing) {
        $table->update($customer_id, $payload);
    } else {
        $payload['id'] = $customer_id;
        $payload['created_at'] = date('Y-m-d H:i:s');
        
        if ($role === 'customer') {
            $pdo = Database::getInstance()->getPDO();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $boolTrue = ($driver === 'pgsql') ? 't' : 1;
            $payload['activated'] = $boolTrue;
            $payload['email_verified'] = $boolTrue;
            $payload['activated_at'] = date('Y-m-d H:i:s');
        }
        
        $table->insert($payload);
    }

    return $customer_id;
}

function delete_customer_data($customer_id) {
    
    // Try to delete from both tables (admins and customers)
    // Check which table has the record
    if (strpos($customer_id, 'admin_') === 0) {
        db_table('admins')->delete($customer_id);
    } else {
        // Check in both tables just to be safe
        $admin = db_table('admins')->find('id', $customer_id);
        $customer = db_table('customers')->find('id', $customer_id);
        
        if ($admin) {
            db_table('admins')->delete($customer_id);
        } elseif ($customer) {
            db_table('customers')->delete($customer_id);
        }
    }
    
    return true;
}

function get_orders_data() {
    $rows = db_table('orders')->all();
    $orders = [];
    foreach ($rows as $row) {
        $items = parse_json_field($row['items'] ?? '');
        if (!is_array($items)) {
            $items = [];
        }
        $notes = parse_json_field($row['notes'] ?? '') ?? [];
        $shipping = $notes['shipping'] ?? [
            'address' => $row['shipping_address'] ?? '',
            'city' => $row['shipping_city'] ?? '',
            'postal_code' => $row['shipping_postal_code'] ?? '',
            'phone' => $row['customer_phone'] ?? ''
        ];
        $payment = $notes['payment'] ?? [
            'method' => $row['payment_method'] ?? '',
            'cod' => ($row['payment_method'] ?? '') === 'cod',
            'status' => $row['payment_status'] ?? 'pending'
        ];
        $customerName = $row['customer_name'] ?? ($notes['customer']['name'] ?? '');
        $customerEmail = $row['customer_email'] ?? ($notes['customer']['email'] ?? '');
        $customerPhone = $row['customer_phone'] ?? ($notes['customer']['phone'] ?? '');
        $customerUser = $notes['customer']['user'] ?? '';

        $orders[$row['id']] = [
            'id' => $row['id'],
            'customer' => [
                'user' => $customerUser,
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone
            ],
            'customer_name' => $customerName,
            'email' => $customerEmail,
            'items' => $items,
            'total' => floatval($row['total'] ?? 0),
            'status' => $row['status'] ?? 'pending',
            'created' => $row['created_at'] ?? '',
            'shipping' => $shipping,
            'payment' => $payment
        ];
    }
    return $orders;
}

function save_order_data($orderData) {
    $table = db_table('orders');
    $order_id = $orderData['id'] ?? uniqid('order_');
    $itemsPayload = json_encode($orderData['items'] ?? [], JSON_UNESCAPED_UNICODE);
    $notesPayload = json_encode([
        'shipping' => $orderData['shipping'] ?? [],
        'payment' => $orderData['payment'] ?? [],
        'customer' => $orderData['customer'] ?? []
    ], JSON_UNESCAPED_UNICODE);

    $payload = [
        'order_number' => $order_id,
        'customer_id' => null,
        'customer_email' => $orderData['customer']['email'] ?? '',
        'customer_name' => $orderData['customer']['name'] ?? '',
        'customer_phone' => $orderData['customer']['phone'] ?? '',
        'shipping_address' => $orderData['shipping']['address'] ?? '',
        'shipping_city' => $orderData['shipping']['city'] ?? '',
        'shipping_postal_code' => $orderData['shipping']['postal_code'] ?? '',
        'shipping_country' => 'Bulgaria',
        'items' => $itemsPayload,
        'subtotal' => floatval($orderData['subtotal'] ?? $orderData['total'] ?? 0),
        'shipping_cost' => floatval($orderData['shipping_cost'] ?? 0),
        'discount' => floatval($orderData['discount_amount'] ?? 0),
        'total' => floatval($orderData['total'] ?? 0),
        'status' => $orderData['status'] ?? 'pending',
        'payment_method' => $orderData['payment']['method'] ?? '',
        'payment_status' => $orderData['payment']['status'] ?? 'pending',
        'notes' => $notesPayload,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $order_id);
    if ($existing) {
        $table->update($order_id, $payload);
    } else {
        $payload['id'] = $order_id;
        $payload['created_at'] = $orderData['created'] ?? date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    return $order_id;
}

function update_order_status_data($order_id, $status) {
    return true;

    db_table('orders')->update($order_id, [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    return true;
}

function delete_order_data($order_id) {
    db_table('orders')->delete($order_id);
    return true;
}

function get_inquiries_data() {
    $rows = db_table('inquiries')->all();
    $inquiries = [];
    foreach ($rows as $row) {
        $notes = parse_json_field($row['notes'] ?? '') ?? [];
        $category = $notes['category'] ?? 'general';
        $username = $notes['username'] ?? ($row['name'] ?? '');
        $instagram = $notes['instagram'] ?? '';

        $inquiries[$row['id']] = [
            'id' => $row['id'],
            'username' => $username,
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'instagram' => $instagram,
            'subject' => $row['subject'] ?? '',
            'category' => $category,
            'message' => $row['message'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'created' => $row['created_at'] ?? ''
        ];
    }
    return $inquiries;
}

function save_inquiry_data($data) {
    $table = db_table('inquiries');
    $inquiry_id = $data['id'] ?? uniqid('inq_');
    $notesPayload = json_encode([
        'category' => $data['category'] ?? 'general',
        'username' => $data['username'] ?? '',
        'instagram' => $data['instagram'] ?? ''
    ], JSON_UNESCAPED_UNICODE);

    $payload = [
        'name' => $data['username'] ?? '',
        'email' => $data['email'] ?? '',
        'phone' => $data['phone'] ?? '',
        'subject' => $data['subject'] ?? '',
        'message' => $data['message'] ?? '',
        'status' => $data['status'] ?? 'pending',
        'notes' => $notesPayload,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $inquiry_id);
    if ($existing) {
        $table->update($inquiry_id, $payload);
    } else {
        $payload['id'] = $inquiry_id;
        $payload['created_at'] = $data['created'] ?? date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    return $inquiry_id;
}

function update_inquiry_status_data($inquiry_id, $status) {
    db_table('inquiries')->update($inquiry_id, [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    return true;
}

function delete_inquiry_data($inquiry_id) {
    db_table('inquiries')->delete($inquiry_id);
    return true;
}

function get_discounts_data() {
    $rows = db_table('discounts')->all();
    $discounts = [];
    foreach ($rows as $row) {
        $discounts[$row['id']] = [
            'id' => $row['id'],
            'code' => $row['code'] ?? '',
            'description' => $row['description'] ?? '',
            'type' => $row['type'] ?? 'percentage',
            'value' => floatval($row['value'] ?? 0),
            'min_purchase' => floatval($row['min_purchase'] ?? 0),
            'max_uses' => intval($row['max_uses'] ?? 0),
            'used_count' => intval($row['uses_count'] ?? 0),
            'start_date' => $row['valid_from'] ?? '',
            'end_date' => $row['valid_until'] ?? '',
            'active' => ($row['status'] ?? 'active') === 'active',
            'first_purchase_only' => !empty($row['first_purchase_only']),
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }
    return $discounts;
}

function save_discount_data($data) {
    $table = db_table('discounts');
    $discount_id = $data['id'] ?: uniqid('disc_');
    
    // Handle empty dates properly (null instead of empty string)
    $valid_from = !empty($data['start_date']) ? $data['start_date'] : null;
    $valid_until = !empty($data['end_date']) ? $data['end_date'] : null;
    
    $payload = [
        'code' => strtoupper($data['code'] ?? ''),
        'description' => $data['description'] ?? '',
        'type' => $data['type'] ?? 'percentage',
        'value' => floatval($data['value'] ?? 0),
        'min_purchase' => floatval($data['min_purchase'] ?? 0),
        'max_uses' => intval($data['max_uses'] ?? 0),
        'uses_count' => intval($data['used_count'] ?? 0),
        'valid_from' => $valid_from,
        'valid_until' => $valid_until,
        'status' => !empty($data['active']) ? 'active' : 'inactive',
        'first_purchase_only' => !empty($data['first_purchase_only']),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $discount_id);
    if ($existing) {
        $table->update($discount_id, $payload);
    } else {
        $payload['id'] = $discount_id;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    return $discount_id;
}

function delete_discount_data($discount_id) {
    db_table('discounts')->delete($discount_id);
    return true;
}

function update_discount_usage($discount_id) {
    $row = db_table('discounts')->find('id', $discount_id);
    if ($row) {
        $current = intval($row['uses_count'] ?? 0) + 1;
        db_table('discounts')->update($discount_id, [
            'uses_count' => $current,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    return true;
}

function get_promotions_data() {
    $rows = db_table('promotions')->all();
    $promotions = [];
    foreach ($rows as $row) {
        $data = parse_json_field($row['data'] ?? '') ?? [];
        if (!empty($data)) {
            $promotions[$row['id']] = $data;
            continue;
        }

        $promotions[$row['id']] = [
            'id' => $row['id'],
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'type' => $row['applies_to'] ?? 'banner',
            'discount_value' => floatval($row['discount_percentage'] ?? 0),
            'start_date' => $row['valid_from'] ?? '',
            'end_date' => $row['valid_until'] ?? '',
            'active' => ($row['status'] ?? 'active') === 'active',
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }

    return $promotions;
}

function save_promotion_data($data) {
    $table = db_table('promotions');
    $promotion_id = $data['id'] ?: uniqid('promo_');
    
    // Handle empty dates properly (null instead of empty string)
    $valid_from = !empty($data['start_date']) ? $data['start_date'] : null;
    $valid_until = !empty($data['end_date']) ? $data['end_date'] : null;
    
    $payload = [
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'discount_percentage' => floatval($data['discount_value'] ?? 0),
        'applies_to' => $data['type'] ?? 'banner',
        'target_ids' => json_encode($data['product_ids'] ?? [], JSON_UNESCAPED_UNICODE),
        'valid_from' => $valid_from,
        'valid_until' => $valid_until,
        'status' => !empty($data['active']) ? 'active' : 'inactive',
        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $promotion_id);
    if ($existing) {
        $table->update($promotion_id, $payload);
    } else {
        $payload['id'] = $promotion_id;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    return $promotion_id;
}

function delete_promotion_data($promotion_id) {
    db_table('promotions')->delete($promotion_id);
    return true;
}

/**
 * Get active homepage promotions (banners, popups, etc.)
 * @param string $type Filter by promotion type ('homepage', 'banner', 'popup', 'notification')
 * @return array Active promotions sorted by order
 */
function get_active_homepage_promotions($type = 'homepage') {
    $promotions = get_promotions_data();
    $now = time();
    
    // Filter active promotions of specified type within date range
    $active = array_filter($promotions, function($promo) use ($now, $type) {
        // Check if promotion is active
        if (!($promo['active'] ?? true)) {
            return false;
        }
        
        // Check promotion type
        if (($promo['type'] ?? '') !== $type) {
            return false;
        }
        
        // Check start date
        if (!empty($promo['start_date']) && strtotime($promo['start_date']) > $now) {
            return false;
        }
        
        // Check end date
        if (!empty($promo['end_date']) && strtotime($promo['end_date']) < $now) {
            return false;
        }
        
        return true;
    });
    
    // Sort by order field (ascending)
    usort($active, function($a, $b) {
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });
    
    return $active;
}

/**
 * Update product count for all categories
 * Counts how many published products belong to each category
 */
function update_category_product_counts() {
    $categories = db_table('categories')->all();
    $products = db_table('products')->all();
    $counts = [];

    foreach ($products as $product) {
        $categorySlug = strtolower(trim($product['category'] ?? ''));
        if ($categorySlug === '') {
            continue;
        }
        $counts[$categorySlug] = ($counts[$categorySlug] ?? 0) + 1;
    }

    foreach ($categories as $category) {
        $slug = strtolower(trim($category['slug'] ?? ''));
        $count = $counts[$slug] ?? 0;
        db_table('categories')->update($category['id'], [
            'product_count' => $count,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Apply active promotions to a product
 * Modifies product price and compare_price based on active sales promotions
 */
function apply_promotions_to_product($product) {
    $promotions = get_promotions_data();
    $now = time();
    
    // Filter active sales promotions
    $active_promotions = array_filter($promotions, function($p) use ($now) {
        $is_active = $p['active'] ?? false;
        $start_ok = empty($p['start_date']) || strtotime($p['start_date']) <= $now;
        $end_ok = empty($p['end_date']) || strtotime($p['end_date']) >= $now;
        $is_sales = in_array($p['type'] ?? '', ['product_discount', 'category_discount', 'cart_discount']);
        return $is_active && $start_ok && $end_ok && $is_sales;
    });
    
    $original_price = $product['price'] ?? 0;
    $best_discount = 0;
    $best_discount_type = '';
    
    foreach ($active_promotions as $promo) {
        $type = $promo['type'] ?? '';
        $apply = false;
        
        // Check if promotion applies to this product
        if ($type === 'product_discount') {
            $product_ids = $promo['product_ids'] ?? [];
            if (!is_array($product_ids)) {
                $product_ids = json_decode($product_ids, true) ?? [];
            }
            $apply = in_array($product['id'] ?? '', $product_ids);
        }
        
        if ($type === 'category_discount') {
            $promo_category = strtolower(trim($promo['category_id'] ?? ''));
            $product_category = strtolower(trim($product['category'] ?? ''));
            $apply = $promo_category === $product_category;
        }
        
        if ($apply) {
            $discount_type = $promo['discount_type'] ?? 'percentage';
            $discount_value = floatval($promo['discount_value'] ?? 0);
            
            if ($discount_type === 'percentage' && $discount_value > $best_discount) {
                $best_discount = $discount_value;
                $best_discount_type = 'percentage';
            } elseif ($discount_type === 'fixed' && $discount_value > 0) {
                // Convert fixed discount to percentage for comparison
                $percentage_equiv = ($discount_value / $original_price) * 100;
                if ($percentage_equiv > $best_discount) {
                    $best_discount = $discount_value;
                    $best_discount_type = 'fixed';
                }
            }
        }
    }
    
    // Apply best discount
    if ($best_discount > 0) {
        $product['compare_price'] = $original_price;
        
        if ($best_discount_type === 'percentage') {
            $product['price'] = $original_price * (1 - $best_discount / 100);
        } else { // fixed
            $product['price'] = max(0, $original_price - $best_discount);
        }
        
        $product['price'] = round($product['price'], 2);
        $product['has_promotion'] = true;
        $product['promotion_discount'] = $best_discount;
    }
    
    return $product;
}

/**
 * Apply promotions to multiple products
 */
function apply_promotions_to_products($products) {
    $result = [];
    foreach ($products as $key => $product) {
        $result[$key] = apply_promotions_to_product($product);
    }
    return $result;
}

// ==========================================
// Analytics Data Functions
// ==========================================

function get_analytics_data() {
    $rows = db_table('analytics_daily')->orderBy('date', 'DESC')->all();
    $analytics = [];
    foreach ($rows as $row) {
        $analytics[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'total_visits' => intval($row['total_visits'] ?? 0),
            'unique_visitors' => intval($row['unique_visitors'] ?? 0),
            'page_views' => intval($row['page_views'] ?? 0),
            'bounce_rate' => floatval($row['bounce_rate'] ?? 0),
            'traffic_sources' => json_decode($row['traffic_sources'] ?? '{}', true),
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? ''
        ];
    }
    return $analytics;
}

function save_analytics_data($data) {
    $table = db_table('analytics_daily');
    $entry_id = $data['id'] ?? null;
    
    $traffic_sources = json_encode([
        'direct' => intval($data['source_direct'] ?? 0),
        'search' => intval($data['source_search'] ?? 0),
        'social' => intval($data['source_social'] ?? 0),
        'referral' => intval($data['source_referral'] ?? 0),
        'email' => intval($data['source_email'] ?? 0)
    ]);
    
    $payload = [
        'date' => $data['date'],
        'total_visits' => intval($data['total_visits'] ?? 0),
        'unique_visitors' => intval($data['unique_visitors'] ?? 0),
        'page_views' => intval($data['page_views'] ?? 0),
        'bounce_rate' => floatval($data['bounce_rate'] ?? 0),
        'traffic_sources' => $traffic_sources,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($entry_id) {
        // Update existing
        $table->update($entry_id, $payload);
    } else {
        // Insert new
        $payload['created_at'] = date('Y-m-d H:i:s');
        $entry_id = $table->insert($payload);
    }
    
    return $entry_id;
}

function delete_analytics_entry($entry_id) {
    db_table('analytics_daily')->delete($entry_id);
}

// ==========================================
// Financial Data Functions
// ==========================================

function get_financial_data() {
    $rows = db_table('financial_data')->orderBy('period_start', 'DESC')->all();
    $financial = [];
    foreach ($rows as $row) {
        $financial[] = [
            'id' => $row['id'],
            'period_start' => $row['period_start'],
            'period_end' => $row['period_end'],
            'total_expenses' => floatval($row['total_expenses'] ?? 0),
            'hosting_costs' => floatval($row['hosting_costs'] ?? 0),
            'marketing_costs' => floatval($row['marketing_costs'] ?? 0),
            'courier_costs' => floatval($row['courier_costs'] ?? 0),
            'other_costs' => floatval($row['other_costs'] ?? 0),
            'tax_rate' => floatval($row['tax_rate'] ?? 20),
            'notes' => $row['notes'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? ''
        ];
    }
    return $financial;
}

function save_financial_data($data) {
    $table = db_table('financial_data');
    $entry_id = $data['id'] ?? null;
    
    $payload = [
        'period_start' => $data['period_start'],
        'period_end' => $data['period_end'],
        'total_expenses' => floatval($data['total_expenses'] ?? 0),
        'hosting_costs' => floatval($data['hosting_costs'] ?? 0),
        'marketing_costs' => floatval($data['marketing_costs'] ?? 0),
        'courier_costs' => floatval($data['courier_costs'] ?? 0),
        'other_costs' => floatval($data['other_costs'] ?? 0),
        'tax_rate' => floatval($data['tax_rate'] ?? 20),
        'notes' => $data['notes'] ?? '',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($entry_id) {
        // Update existing
        $table->update($entry_id, $payload);
    } else {
        // Insert new
        $payload['created_at'] = date('Y-m-d H:i:s');
        $entry_id = $table->insert($payload);
    }
    
    return $entry_id;
}

function delete_financial_entry($entry_id) {
    db_table('financial_data')->delete($entry_id);
}
