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
    $options = load_json('storage/options.json');
    return $options[$key] ?? $default;
}

// Update CMS setting
function update_option($key, $value) {
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

// Get all pages
function get_pages() {
    return load_json('storage/pages.json');
}

// Get page by slug
function get_page($slug) {
    $pages = get_pages();
    return $pages[$slug] ?? null;
}

// Save page
function save_page($slug, $data) {
    $pages = get_pages();
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
    $pages = get_pages();
    unset($pages[$slug]);
    save_json('storage/pages.json', $pages);
    return true;
}

// Get all posts
function get_posts() {
    return load_json('storage/posts.json');
}

// Get post by slug
function get_post($slug) {
    $posts = get_posts();
    return $posts[$slug] ?? null;
}

// Save post
function save_post($slug, $data) {
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
    return true;
}

// Delete post
function delete_post($slug) {
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
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
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

/**
 * Update product count for all categories
 * Counts how many published products belong to each category
 */
function update_category_product_counts() {
    $categories = load_json('storage/categories.json');
    $products = load_json('storage/products.json');
    
    // Reset all counts to 0
    foreach ($categories as &$category) {
        $category['product_count'] = 0;
    }
    
    // Count products per category
    foreach ($products as $product) {
        $categorySlug = $product['category'] ?? '';
        if (empty($categorySlug)) continue;
        
        // Find matching category and increment count
        foreach ($categories as &$category) {
            if ($category['slug'] === $categorySlug) {
                $category['product_count'] = ($category['product_count'] ?? 0) + 1;
                break;
            }
        }
    }
    
    save_json('storage/categories.json', $categories);
}


