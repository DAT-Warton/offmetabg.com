<?php
/**
 * Core CMS Functions
 */

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
    if (db_enabled()) {
        $pdo = Database::getInstance()->getPDO();
        try {
            $stmt = $pdo->prepare('SELECT option_value FROM options WHERE option_key = ?');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (PDOException $e) {
            // Fallback to JSON on error.
        }
    }

    $options = load_json('storage/options.json');
    return $options[$key] ?? $default;
}

// Update CMS setting
function update_option($key, $value) {
    if (db_enabled()) {
        $pdo = Database::getInstance()->getPDO();
        try {
            $stmt = $pdo->prepare('INSERT INTO options (option_key, option_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (option_key) DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([$key, $value]);
            return true;
        } catch (PDOException $e) {
            // Fallback to JSON on error.
        }
    }

    $options = load_json('storage/options.json');
    $options[$key] = $value;
    save_json('storage/options.json', $options);
    return true;
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
    if (!class_exists('Database')) {
        return false;
    }
    $db = Database::getInstance();
    return $db->getDriver() !== 'json' && $db->getPDO();
}

function db_table($name) {
    return Database::getInstance()->table($name);
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

// Get all pages
function get_pages() {
    if (db_enabled()) {
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

    return load_json('storage/pages.json');
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

    if (db_enabled()) {
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

    $pages = get_pages();
    if ($originalSlug === '') {
        $baseSlug = $slug;
        $counter = 2;
        while (isset($pages[$slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
    $pages[$slug] = array_merge($pages[$slug] ?? [], [
        'slug' => $slug,
        'title' => $data['title'] ?? '',
        'content' => $data['content'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'status' => $data['status'] ?? 'published',
        'created' => $pages[$slug]['created'] ?? date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s'),
    ]);
    save_json('storage/pages.json', $pages);
    return true;
}

// Delete page
function delete_page($slug) {
    if (db_enabled()) {
        $table = db_table('pages');
        $existing = $table->find('slug', $slug);
        if ($existing) {
            $table->delete($existing['id']);
        }
        return true;
    }

    $pages = get_pages();
    unset($pages[$slug]);
    save_json('storage/pages.json', $pages);
    return true;
}

// Get all posts
function get_posts() {
    if (db_enabled()) {
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

    return load_json('storage/posts.json');
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
    
    if (db_enabled()) {
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
            $table->update($existing['id'], $payload);
            return $slug;
        }

        $payload['id'] = uniqid('post_');
        $payload['created_at'] = date('Y-m-d H:i:s');
        $table->insert($payload);
        return $slug;
    }

    $posts = get_posts();
    $posts[$slug] = array_merge($posts[$slug] ?? [], [
        'slug' => $slug,
        'title' => $data['title'] ?? '',
        'content' => $data['content'] ?? '',
        'excerpt' => $data['excerpt'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'featured_image' => $data['featured_image'] ?? '',
        'status' => $data['status'] ?? 'published',
        'category' => $data['category'] ?? 'uncategorized',
        'created' => $posts[$slug]['created'] ?? date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s'),
    ]);
    save_json('storage/posts.json', $posts);
    return $slug;
}

// Delete post
function delete_post($slug) {
    if (db_enabled()) {
        $table = db_table('posts');
        $existing = $table->find('slug', $slug);
        if ($existing) {
            $table->delete($existing['id']);
        }
        return true;
    }

    $posts = get_posts();
    unset($posts[$slug]);
    save_json('storage/posts.json', $posts);
    return true;
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
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

// Get stats for dashboard
function get_dashboard_stats() {
    return [
        'total_pages' => count(get_pages()),
        'total_posts' => count(get_posts()),
        'total_users' => count(load_json('storage/users.json')),
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
    
    return '<div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 8px; margin-bottom: 20px;">
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
    if (empty($url)) return null;
    
    if (preg_match('/tiktok\.com\/@[^\/]+\/video\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Generate TikTok embed HTML
function get_tiktok_embed($url) {
    $videoId = get_tiktok_id($url);
    if (!$videoId) return '';
    
    return '<div class="video-container" style="max-width: 605px; margin: 0 auto 20px; background: #000; border-radius: 8px; overflow: hidden;">
        <blockquote class="tiktok-embed" cite="' . htmlspecialchars($url) . '" data-video-id="' . htmlspecialchars($videoId) . '" style="max-width: 605px; min-width: 325px;">
            <section></section>
        </blockquote>
        <script async src="https://www.tiktok.com/embed.js"></script>
    </div>';
}

// Extract Instagram post ID from URL
function get_instagram_id($url) {
    if (empty($url)) return null;
    
    if (preg_match('/instagram\.com\/(p|reel)\/([^\/\?]+)/', $url, $matches)) {
        return $matches[2];
    }
    return null;
}

// Generate Instagram embed HTML
function get_instagram_embed($url) {
    $postId = get_instagram_id($url);
    if (!$postId) return '';
    
    $embedUrl = 'https://www.instagram.com/p/' . $postId . '/embed';
    
    return '<div class="video-container" style="max-width: 540px; margin: 0 auto 20px; background: #fff; border: 1px solid #dbdbdb; border-radius: 3px; overflow: hidden;">
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
    if (!db_enabled()) {
        return load_json('storage/products.json');
    }

    ensure_db_schema();
    $rows = db_table('products')->all();
    $products = [];

    foreach ($rows as $row) {
        $images = parse_json_field($row['images'] ?? null);
        $image = '';
        $videos = [
            'youtube' => '',
            'tiktok' => '',
            'instagram' => ''
        ];

        if (is_array($images)) {
            if (isset($images['primary'])) {
                $image = $images['primary'] ?? '';
                if (isset($images['videos']) && is_array($images['videos'])) {
                    $videos = array_merge($videos, $images['videos']);
                }
            } elseif (isset($images['image'])) {
                $image = $images['image'] ?? '';
                if (isset($images['videos']) && is_array($images['videos'])) {
                    $videos = array_merge($videos, $images['videos']);
                }
            } elseif (array_values($images) === $images && !empty($images[0])) {
                $image = $images[0];
            }
        } elseif (is_string($images)) {
            $image = $images;
        }

        $products[$row['id']] = [
            'id' => $row['id'],
            'name' => $row['name'] ?? '',
            'description' => $row['description'] ?? '',
            'price' => floatval($row['price'] ?? 0),
            'image' => $image,
            'category' => $row['category'] ?? '',
            'stock' => intval($row['stock'] ?? 0),
            'status' => $row['status'] ?? 'published',
            'videos' => $videos,
            'created' => $row['created_at'] ?? '',
            'updated' => $row['updated_at'] ?? ''
        ];
    }

    return $products;
}

function save_product_data($data) {
    if (!db_enabled()) {
        $products = load_json('storage/products.json');
        $product_id = $data['id'] ?: uniqid('prod_');
        $products[$product_id] = [
            'id' => $product_id,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'price' => floatval($data['price'] ?? 0),
            'image' => $data['image'] ?? '',
            'category' => $data['category'] ?? 'general',
            'stock' => intval($data['stock'] ?? 0),
            'status' => $data['status'] ?? 'published',
            'videos' => $data['videos'] ?? [],
            'created' => $products[$product_id]['created'] ?? date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        save_json('storage/products.json', $products);
        update_category_product_counts();
        return $product_id;
    }

    ensure_db_schema();
    $table = db_table('products');
    $product_id = $data['id'] ?: uniqid('prod_');
    $videos = $data['videos'] ?? [];
    $mediaPayload = json_encode([
        'primary' => $data['image'] ?? '',
        'videos' => $videos
    ], JSON_UNESCAPED_UNICODE);

    $payload = [
        'slug' => $data['slug'] ?? generate_slug($data['name'] ?? $product_id),
        'name' => $data['name'] ?? '',
        'description' => $data['description'] ?? '',
        'price' => floatval($data['price'] ?? 0),
        'category' => $data['category'] ?? 'general',
        'images' => $mediaPayload,
        'stock' => intval($data['stock'] ?? 0),
        'status' => $data['status'] ?? 'published',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $existing = $table->find('id', $product_id);
    if ($existing) {
        $table->update($product_id, $payload);
    } else {
        $payload['id'] = $product_id;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $table->insert($payload);
    }

    update_category_product_counts();
    return $product_id;
}

function delete_product_data($product_id) {
    if (!db_enabled()) {
        $products = load_json('storage/products.json');
        unset($products[$product_id]);
        save_json('storage/products.json', $products);
        update_category_product_counts();
        return true;
    }

    ensure_db_schema();
    db_table('products')->delete($product_id);
    update_category_product_counts();
    return true;
}

function get_categories_data() {
    if (!db_enabled()) {
        return load_json('storage/categories.json');
    }

    ensure_db_schema();
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
    if (!db_enabled()) {
        $categories = load_json('storage/categories.json');
        $category_id = $data['id'] ?: uniqid('cat_');
        $categories[$category_id] = [
            'id' => $category_id,
            'name' => $data['name'] ?? '',
            'slug' => $data['slug'] ?? '',
            'description' => $data['description'] ?? '',
            'parent_id' => $data['parent_id'] ?? '',
            'icon' => $data['icon'] ?? '',
            'order' => intval($data['order'] ?? 0),
            'active' => !empty($data['active']),
            'product_count' => $categories[$category_id]['product_count'] ?? 0,
            'created' => $categories[$category_id]['created'] ?? date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        save_json('storage/categories.json', $categories);
        update_category_product_counts();
        return $category_id;
    }

    ensure_db_schema();
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
    if (!db_enabled()) {
        $categories = load_json('storage/categories.json');
        unset($categories[$category_id]);
        save_json('storage/categories.json', $categories);
        update_category_product_counts();
        return true;
    }

    ensure_db_schema();
    db_table('categories')->delete($category_id);
    update_category_product_counts();
    return true;
}

function get_customers_data() {
    if (!db_enabled()) {
        return load_json('storage/customers.json');
    }

    ensure_db_schema();
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
    if (!db_enabled()) {
        $customers = load_json('storage/customers.json');
        $customer_id = $data['id'] ?: uniqid('cust_');
        $customers[$customer_id] = [
            'id' => $customer_id,
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $data['password'] ?? ($customers[$customer_id]['password'] ?? ''),
            'role' => $data['role'] ?? 'customer',
            'permissions' => $data['permissions'] ?? ['view_products', 'place_orders'],
            'activated' => $customers[$customer_id]['activated'] ?? true,
            'created' => $customers[$customer_id]['created'] ?? date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        save_json('storage/customers.json', $customers);
        return $customer_id;
    }

    ensure_db_schema();
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
            $payload['activated'] = true;
            $payload['email_verified'] = true;
            $payload['activated_at'] = date('Y-m-d H:i:s');
        }
        
        $table->insert($payload);
    }

    return $customer_id;
}

function delete_customer_data($customer_id) {
    if (!db_enabled()) {
        $customers = load_json('storage/customers.json');
        unset($customers[$customer_id]);
        save_json('storage/customers.json', $customers);
        return true;
    }

    ensure_db_schema();
    
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
    if (!db_enabled()) {
        return load_json('storage/orders.json');
    }

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
    if (!db_enabled()) {
        $orders = load_json('storage/orders.json');
        $order_id = $orderData['id'] ?? uniqid('order_');
        $orders[$order_id] = $orderData;
        save_json('storage/orders.json', $orders);
        return $order_id;
    }

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
    if (!db_enabled()) {
        $orders = load_json('storage/orders.json');
        if (isset($orders[$order_id])) {
            $orders[$order_id]['status'] = $status;
            $orders[$order_id]['updated'] = date('Y-m-d H:i:s');
            save_json('storage/orders.json', $orders);
        }
        return true;
    }

    db_table('orders')->update($order_id, [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    return true;
}

function delete_order_data($order_id) {
    if (!db_enabled()) {
        $orders = load_json('storage/orders.json');
        unset($orders[$order_id]);
        save_json('storage/orders.json', $orders);
        return true;
    }

    db_table('orders')->delete($order_id);
    return true;
}

function get_inquiries_data() {
    if (!db_enabled()) {
        return load_json('storage/inquiries.json');
    }

    $rows = db_table('inquiries')->all();
    $inquiries = [];
    foreach ($rows as $row) {
        $notes = parse_json_field($row['notes'] ?? '') ?? [];
        $category = $notes['category'] ?? 'general';
        $username = $notes['username'] ?? ($row['name'] ?? '');

        $inquiries[$row['id']] = [
            'id' => $row['id'],
            'username' => $username,
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
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
    if (!db_enabled()) {
        $inquiries = load_json('storage/inquiries.json');
        $inquiry_id = $data['id'] ?? uniqid('inq_');
        $inquiries[$inquiry_id] = $data;
        save_json('storage/inquiries.json', $inquiries);
        return $inquiry_id;
    }

    $table = db_table('inquiries');
    $inquiry_id = $data['id'] ?? uniqid('inq_');
    $notesPayload = json_encode([
        'category' => $data['category'] ?? 'general',
        'username' => $data['username'] ?? ''
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
    if (!db_enabled()) {
        $inquiries = load_json('storage/inquiries.json');
        if (isset($inquiries[$inquiry_id])) {
            $inquiries[$inquiry_id]['status'] = $status;
            $inquiries[$inquiry_id]['updated'] = date('Y-m-d H:i:s');
            save_json('storage/inquiries.json', $inquiries);
        }
        return true;
    }

    db_table('inquiries')->update($inquiry_id, [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    return true;
}

function delete_inquiry_data($inquiry_id) {
    if (!db_enabled()) {
        $inquiries = load_json('storage/inquiries.json');
        unset($inquiries[$inquiry_id]);
        save_json('storage/inquiries.json', $inquiries);
        return true;
    }

    db_table('inquiries')->delete($inquiry_id);
    return true;
}

function get_discounts_data() {
    if (!db_enabled()) {
        return load_json('storage/discounts.json');
    }

    ensure_db_schema();
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
    if (!db_enabled()) {
        $discounts = load_json('storage/discounts.json');
        $discount_id = $data['id'] ?: uniqid('disc_');
        $discounts[$discount_id] = $data;
        save_json('storage/discounts.json', $discounts);
        return $discount_id;
    }

    ensure_db_schema();
    $table = db_table('discounts');
    $discount_id = $data['id'] ?: uniqid('disc_');
    $payload = [
        'code' => strtoupper($data['code'] ?? ''),
        'description' => $data['description'] ?? '',
        'type' => $data['type'] ?? 'percentage',
        'value' => floatval($data['value'] ?? 0),
        'min_purchase' => floatval($data['min_purchase'] ?? 0),
        'max_uses' => intval($data['max_uses'] ?? 0),
        'uses_count' => intval($data['used_count'] ?? 0),
        'valid_from' => $data['start_date'] ?? null,
        'valid_until' => $data['end_date'] ?? null,
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
    if (!db_enabled()) {
        $discounts = load_json('storage/discounts.json');
        unset($discounts[$discount_id]);
        save_json('storage/discounts.json', $discounts);
        return true;
    }

    db_table('discounts')->delete($discount_id);
    return true;
}

function update_discount_usage($discount_id) {
    if (!db_enabled()) {
        $discounts = load_json('storage/discounts.json');
        if (isset($discounts[$discount_id])) {
            $discounts[$discount_id]['used_count'] = intval($discounts[$discount_id]['used_count'] ?? 0) + 1;
            save_json('storage/discounts.json', $discounts);
        }
        return true;
    }

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
    if (!db_enabled()) {
        return load_json('storage/promotions.json');
    }

    ensure_db_schema();
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
    if (!db_enabled()) {
        $promotions = load_json('storage/promotions.json');
        $promotion_id = $data['id'] ?: uniqid('promo_');
        $promotions[$promotion_id] = $data;
        save_json('storage/promotions.json', $promotions);
        return $promotion_id;
    }

    ensure_db_schema();
    $table = db_table('promotions');
    $promotion_id = $data['id'] ?: uniqid('promo_');
    $payload = [
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'discount_percentage' => floatval($data['discount_value'] ?? 0),
        'applies_to' => $data['type'] ?? 'banner',
        'target_ids' => json_encode($data['product_ids'] ?? [], JSON_UNESCAPED_UNICODE),
        'valid_from' => $data['start_date'] ?? null,
        'valid_until' => $data['end_date'] ?? null,
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
    if (!db_enabled()) {
        $promotions = load_json('storage/promotions.json');
        unset($promotions[$promotion_id]);
        save_json('storage/promotions.json', $promotions);
        return true;
    }

    db_table('promotions')->delete($promotion_id);
    return true;
}

/**
 * Update product count for all categories
 * Counts how many published products belong to each category
 */
function update_category_product_counts() {
    if (!db_enabled()) {
        $categories = load_json('storage/categories.json');
        $products = load_json('storage/products.json');

        foreach ($categories as &$category) {
            $category['product_count'] = 0;
        }

        foreach ($products as $product) {
            $categorySlug = $product['category'] ?? '';
            if (empty($categorySlug)) {
                continue;
            }

            foreach ($categories as &$category) {
                if ($category['slug'] === $categorySlug) {
                    $category['product_count'] = ($category['product_count'] ?? 0) + 1;
                    break;
                }
            }
        }

        save_json('storage/categories.json', $categories);
        return;
    }

    ensure_db_schema();
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


