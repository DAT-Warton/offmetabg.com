<?php
/**
 * Shop Homepage - Product Listing
 */

// Load language system
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/icons.php';

// Get currency settings from database
$currency_settings = get_currency_settings();
$default_currency = $currency_settings['symbol'];

// Get products and apply active promotions/discounts
$products = get_products_data();
$products = apply_promotions_to_products($products);
$published_products = array_filter($products, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});

// Sort by created date (newest first) and limit to 6
usort($published_products, function($a, $b) {
    $dateA = $a['created'] ?? '2000-01-01';
    $dateB = $b['created'] ?? '2000-01-01';
    return strtotime($dateB) - strtotime($dateA);
});
$published_products = array_slice($published_products, 0, 6);

// Get customer info if logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$user_name = $_SESSION['customer_user'] ?? $_SESSION['admin_user'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';

// Get cart count
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'quantity'));

// Get active homepage promotions
$homepage_promotions = get_active_homepage_promotions('homepage');

// Get sidebar banners (max 2 per side)
$sidebar_left_banners = array_slice(get_active_homepage_promotions('sidebar_left'), 0, 2);
$sidebar_right_banners = array_slice(get_active_homepage_promotions('sidebar_right'), 0, 2);

// Get categories - show ALL categories
$categories = get_categories_data();
$activeCategories = $categories; // Show all categories, not just active ones
usort($activeCategories, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

// Get latest blog posts (get 3 for grid display)
$posts = get_posts();
$published_posts = array_filter($posts, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});
usort($published_posts, function($a, $b) {
    $dateA = $a['created'] ?? '2000-01-01';
    $dateB = $b['created'] ?? '2000-01-01';
    return strtotime($dateB) - strtotime($dateA);
});
$latest_posts = array_slice($published_posts, 0, 3); // Get latest 3 posts
$latest_post = !empty($published_posts) ? reset($published_posts) : null; // Keep for backward compatibility

$site_title = htmlspecialchars(get_option('site_title', __('site_name')));
$site_description = htmlspecialchars(get_option('site_description', __('homepage.meta_description')));
$categoryLabelBySlug = [];
foreach ($categories as $category) {
    $slug = strtolower(trim($category['slug'] ?? ''));
    if ($slug !== '') {
        $categoryLabelBySlug[$slug] = $category['name'] ?? $slug;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description"content="<?php echo $site_description; ?>">
    <meta property="og:title"content="<?php echo $site_title; ?>">
    <meta property="og:description"content="<?php echo $site_description; ?>">
    
    <!-- Favicon -->
    <link rel="icon"type="image/svg+xml"href="/favicon.svg">
    <link rel="icon"type="image/x-icon"href="/favicon.ico">
    
    <!-- Preload critical resources for faster LCP -->
    <!-- removed per-page preload for home.min.css, using unified app CSS -->
    <?php
    $logo_url = get_site_setting('logo_url', '');
    if (!empty($logo_url)):
        $logo_small = str_replace('.png', '_small.png', $logo_url);
    ?>
    <link rel="preload"href="<?php echo htmlspecialchars($logo_small); ?>"as="image"fetchpriority="high">
    <?php endif; ?>
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect"href="https://fonts.googleapis.com">
    <link rel="preconnect"href="https://fonts.gstatic.com"crossorigin>
    <!-- zaeshkatadupka will be removed in near future-->
    <link rel="dns-prefetch"href="https://zaeshkatadupka.eu">
    
    <!-- Unified CSS -->
    <link rel="preload" href="/assets/css/themes.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="stylesheet" href="/assets/css/themes.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
    <?php echo get_custom_theme_css(); ?>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo"style="justify-content: <?php echo get_site_setting('logo_position', 'left'); ?>;">
                <?php 
                $logo_url = get_site_setting('logo_url', '');
                $logo_max_height = get_site_setting('logo_max_height', '50');
                $logo_max_width = get_site_setting('logo_max_width', '200');
                $show_site_name = get_site_setting('show_site_name', 'true') === 'true';
                
                if (!empty($logo_url)): 
                    $logo_small = str_replace('.png', '_small.png', $logo_url);
                    $cache_buster = '?v=' . filemtime(__DIR__ . '/../uploads/settings/' . basename($logo_small));
                ?>
                    <img src="<?php echo htmlspecialchars($logo_small . $cache_buster); ?>"
                         alt="<?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>"
                         width="<?php echo intval($logo_max_width); ?>"
                         height="<?php echo intval($logo_max_height); ?>"
                         fetchpriority="high"
                         style="max-height: <?php echo intval($logo_max_height); ?>px; max-width: <?php echo intval($logo_max_width); ?>px; object-fit: contain;">
                <?php endif; ?>
                
                <?php if ($show_site_name || empty($logo_url)): ?>
                    <h1><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></h1>
                <?php endif; ?>
            </div>
            <div class="nav-buttons">
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle Menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                
                <!-- Desktop + Mobile Nav -->
                <nav class="main-nav" id="mainNav">
                    <a href="/blog" class="btn btn-secondary"><?php echo __('menu.blog_posts'); ?></a>
                    <a href="/about" class="btn btn-secondary"><?php echo __('about_link'); ?></a>
                    <a href="inquiries.php" class="btn btn-secondary"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a>
                    
                    <?php if ($is_logged_in): ?>
                        <?php if ($user_role === 'admin'): ?>
                            <a href="/admin/index.php" class="btn btn-secondary" title="<?php echo __('admin_panel'); ?>">
                                <?php echo icon_settings(18); ?> <span class="btn-text"><?php echo __('admin_panel'); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/auth.php?action=login" class="btn btn-primary"><?php echo __('login'); ?></a>
                        <a href="/auth.php?action=register" class="btn btn-secondary"><?php echo __('register'); ?></a>
                    <?php endif; ?>
                </nav>
                
                <!-- Always Visible Controls -->
                <div class="nav-controls">
                    <a href="?lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language" aria-label="Switch Language">
                        <?php echo strtoupper(opposite_lang()); ?>
                    </a>
                    
                    <a href="cart.php" class="btn btn-cart">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 2L7 6H2L7 22H17L22 6H17L15 2H9Z"/>
                        </svg>
                        <span class="cart-text"><?php echo __('cart_button'); ?></span>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if ($is_logged_in): ?>
                        <?php require_once __DIR__ . '/../includes/profile-dropdown.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="hero">
        <div class="hero-inner">
            <p class="hero-kicker"><?php echo __('homepage.hero_kicker'); ?></p>
            <p class="hero-subtitle"><?php echo __('homepage.hero_subtitle'); ?></p>
            <div class="hero-actions">
                <?php 
                $facebook_url = get_site_setting('facebook_url', 'https://www.facebook.com/offmetabg', 'social');
                $instagram_url = get_site_setting('instagram_url', 'https://www.instagram.com/offmetabg', 'social');
                $tiktok_url = get_site_setting('tiktok_url', 'https://www.tiktok.com/@offmetabg', 'social');
                
                if (!empty($facebook_url)): ?>
                <a class="btn btn-primary hero-social"href="<?php echo htmlspecialchars($facebook_url); ?>"target="_blank"rel="noopener noreferrer"title="Facebook">
                    Facebook
                </a>
                <?php endif; ?>
                <?php if (!empty($instagram_url)): ?>
                <a class="btn btn-primary hero-social"href="<?php echo htmlspecialchars($instagram_url); ?>"target="_blank"rel="noopener noreferrer"title="Instagram">
                    Instagram
                </a>
                <?php endif; ?>
                <?php if (!empty($tiktok_url)): ?>
                <a class="btn btn-primary hero-social"href="<?php echo htmlspecialchars($tiktok_url); ?>"target="_blank"rel="noopener noreferrer"title="TikTok">
                    TikTok
                </a>
                <?php endif; ?>
            </div>
            <p class="hero-meta"><?php echo __('homepage.hero_meta'); ?></p>
        </div>
    </div>

    <?php if (!empty($homepage_promotions)): ?>
    <!-- Homepage Promotions -->
    <section class="promotions-section">
        <div class="container">
            <?php foreach ($homepage_promotions as $promotion): ?>
                <div class="promotion-banner">
                    <?php if (!empty($promotion['link'])): ?>
                        <a href="<?php echo htmlspecialchars($promotion['link']); ?>"class="promotion-link">
                    <?php endif; ?>
                    
                    <?php if (!empty($promotion['image'])): ?>
                        <img src="<?php echo htmlspecialchars($promotion['image']); ?>"
                             alt="<?php echo htmlspecialchars($promotion['title']); ?>"
                             class="promotion-image"
                             loading="lazy">
                    <?php endif; ?>
                    
                    <div class="promotion-content">
                        <h3 class="promotion-title"><?php echo htmlspecialchars($promotion['title']); ?></h3>
                        <?php if (!empty($promotion['description'])): ?>
                            <p class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($promotion['link'])): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($activeCategories)): ?>
    <section class="categories-section"id="categories">
        <div class="container">
            <div class="content-with-sidebars">
                <!-- Left Sidebar Banners -->
                <?php if (!empty($sidebar_left_banners)): ?>
                <aside class="sidebar-banners sidebar-left">
                    <?php foreach ($sidebar_left_banners as $banner): ?>
                        <div class="sidebar-banner">
                            <?php if (!empty($banner['link'])): ?>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>"target="_blank"rel="noopener">
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['image'])): ?>
                                <img src="<?php echo htmlspecialchars($banner['image']); ?>"
                                     alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="sidebar-banner-text">
                                    <h4><?php echo htmlspecialchars($banner['title']); ?></h4>
                                    <?php if (!empty($banner['description'])): ?>
                                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['link'])): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </aside>
                <?php endif; ?>
                
                <!-- Main Content -->
                <div class="main-content">
            <h2 class="section-title"><?php echo __('categories'); ?></h2>
            <div class="categories-grid">
                <?php foreach ($activeCategories as $category):
                    // Get the latest product from this category (from all products, not just the 6 shown)
                    $categoryProducts = array_filter($products, function($product) use ($category) {
                        $productCategory = strtolower(trim($product['category'] ?? ''));
                        $categorySlug = strtolower(trim($category['slug'] ?? ''));
                        $categoryName = strtolower(trim($category['name'] ?? ''));
                        return in_array($productCategory, [$categorySlug, $categoryName], true) &&
                               ($product['status'] ?? 'published') === 'published';
                    });
                    
                    // Sort by created date (newest first)
                    usort($categoryProducts, function($a, $b) {
                        $dateA = $a['created'] ?? '2000-01-01';
                        $dateB = $b['created'] ?? '2000-01-01';
                        return strtotime($dateB) - strtotime($dateA);
                    });
                    
                    $sampleProduct = !empty($categoryProducts) ? reset($categoryProducts) : null;
                ?>
                    <a href="/category/<?php echo urlencode($category['slug']); ?>"class="category-card">
                        <?php if ($sampleProduct && !empty($sampleProduct['image'])): ?>
                            <div class="category-preview-image"style="background-image: url('<?php echo htmlspecialchars($sampleProduct['image']); ?>');"></div>
                        <?php else: ?>
                            <div class="category-preview-image no-image"></div>
                        <?php endif; ?>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
                </div><!-- /main-content -->
                
                <!-- Right Sidebar Banners -->
                <?php if (!empty($sidebar_right_banners)): ?>
                <aside class="sidebar-banners sidebar-right">
                    <?php foreach ($sidebar_right_banners as $banner): ?>
                        <div class="sidebar-banner">
                            <?php if (!empty($banner['link'])): ?>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>"target="_blank"rel="noopener">
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['image'])): ?>
                                <img src="<?php echo htmlspecialchars($banner['image']); ?>"
                                     alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="sidebar-banner-text">
                                    <h4><?php echo htmlspecialchars($banner['title']); ?></h4>
                                    <?php if (!empty($banner['description'])): ?>
                                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['link'])): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </aside>
                <?php endif; ?>
            </div><!-- /content-with-sidebars -->
        </div>
    </section>
    <?php endif; ?>

    <section class="products-section"id="products">
        <div class="container">
            <div class="content-with-sidebars">
                <!-- Left Sidebar Banners -->
                <?php if (!empty($sidebar_left_banners)): ?>
                <aside class="sidebar-banners sidebar-left">
                    <?php foreach ($sidebar_left_banners as $banner): ?>
                        <div class="sidebar-banner">
                            <?php if (!empty($banner['link'])): ?>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>"target="_blank"rel="noopener">
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['image'])): ?>
                                <img src="<?php echo htmlspecialchars($banner['image']); ?>"
                                     alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="sidebar-banner-text">
                                    <h4><?php echo htmlspecialchars($banner['title']); ?></h4>
                                    <?php if (!empty($banner['description'])): ?>
                                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['link'])): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </aside>
                <?php endif; ?>
                
                <!-- Main Content -->
                <div class="main-content">
            <h2 class="section-title"><?php echo __('homepage.new_products'); ?></h2>
            
            <?php if (empty($published_products)): ?>
                <div class="empty-state">
                    <h3><?php echo __('homepage.no_products_title'); ?></h3>
                    <p><?php echo __('homepage.no_products_text'); ?></p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($published_products as $product):
                        $stock = $product['stock'] ?? 0;
                        $price_eur = $product['price'] ?? 0;
                        $productCategoryRaw = (string)($product['category'] ?? '');
                        $productCategoryKey = strtolower(trim($productCategoryRaw));
                        $productCategoryLabel = $categoryLabelBySlug[$productCategoryKey] ?? $productCategoryRaw;
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         width="400"
                                         height="400"
                                         loading="lazy"
                                         decoding="async">
                                <?php endif; ?>
                                <?php if (!empty($productCategoryLabel)): ?>
                                    <span class="product-badge"><?php echo htmlspecialchars($productCategoryLabel); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <?php if (!empty($productCategoryLabel)): ?>
                                    <span class="product-category"><?php echo htmlspecialchars($productCategoryLabel); ?></span>
                                <?php endif; ?>
                                <h3 class="product-title"><?php echo html_entity_decode(htmlspecialchars($product['name']), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($product['short_description'])): ?>
                                    <p class="product-short-description" style="color: #666; font-size: 0.9em; margin: 8px 0; line-height: 1.4; max-height: 2.8em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo strip_tags(html_entity_decode($product['short_description'], ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($product['slug'])): ?>
                                    <a href="/product/<?php echo urlencode($product['slug']); ?>"class="btn-learn-more">
                                        📖 <?php echo __('product.learn_more'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <div class="product-footer">
                                    <div>
                                        <div class="product-price">
                                            <?php if ($price_eur <= 0): ?>
                                                <span class="contact-price" style="color: #3498db; font-weight: 600; font-style: italic;">
                                                    📞 Свържете се за цена
                                                </span>
                                            <?php elseif (!empty($product['compare_price']) && $product['compare_price'] > $price_eur): ?>
                                                <?php 
                                                $dual_compare = get_dual_currency_price($product['compare_price'], 'EUR');
                                                $dual_sale = get_dual_currency_price($price_eur, 'EUR');
                                                ?>
                                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                                    <div>
                                                        <span class="original-price" style="text-decoration: line-through; color: #999; font-size: 0.85em;">
                                                            <?php echo number_format($dual_compare['eur'], 2); ?> € (<?php echo number_format($dual_compare['bgn'], 2); ?> лв.)
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span class="sale-price" style="color: #e74c3c; font-weight: 700; font-size: 1.1em;">
                                                            <?php echo number_format($dual_sale['eur'], 2); ?> €
                                                        </span>
                                                        <span class="sale-price-bgn" style="color: #666; font-size: 0.9em;">
                                                            (<?php echo number_format($dual_sale['bgn'], 2); ?> лв.)
                                                        </span>
                                                        <span class="discount-badge" style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75em;">
                                                            -<?php echo round((1 - $price_eur / $product['compare_price']) * 100); ?>%
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <?php 
                                                $dual_price = get_dual_currency_price($price_eur, 'EUR');
                                                ?>
                                                <span class="price-eur" style="font-weight: 700; font-size: 1.1em;">
                                                    <?php echo number_format($dual_price['eur'], 2); ?> €
                                                </span>
                                                <span class="price-bgn" style="color: #666; font-size: 0.9em; margin-left: 6px;">
                                                    (<?php echo number_format($dual_price['bgn'], 2); ?> лв.)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($stock > 0): ?>
                                            <div class="stock-indicator <?php echo $stock <= 5 ? 'low' : ''; ?>">
                                                ✓ <?php echo $stock <= 5 ? "Само $stock бр.": "В наличност"; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="stock-indicator out">✗ Изчерпан</div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST"action="cart.php">
                                        <input type="hidden"name="action"value="add">
                                        <input type="hidden"name="product_id"value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <button type="submit"class="btn-buy"<?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                            🛒 Купи
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
                </div><!-- /main-content -->
                
                <!-- Right Sidebar Banners -->
                <?php if (!empty($sidebar_right_banners)): ?>
                <aside class="sidebar-banners sidebar-right">
                    <?php foreach ($sidebar_right_banners as $banner): ?>
                        <div class="sidebar-banner">
                            <?php if (!empty($banner['link'])): ?>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>"target="_blank"rel="noopener">
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['image'])): ?>
                                <img src="<?php echo htmlspecialchars($banner['image']); ?>"
                                     alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="sidebar-banner-text">
                                    <h4><?php echo htmlspecialchars($banner['title']); ?></h4>
                                    <?php if (!empty($banner['description'])): ?>
                                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['link'])): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </aside>
                <?php endif; ?>
            </div><!-- /content-with-sidebars -->
        </div>
    </section>

    <section class="latest-post-section"id="blog">
        <div class="container">
            <h2 class="section-title"><?php echo __('homepage.latest_blog_post'); ?></h2>
            <?php if (!empty($latest_posts)): ?>
            <div class="blog-posts-grid">
                <?php foreach ($latest_posts as $post): ?>
                <div class="blog-post-card">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="blog-post-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php else: ?>
                        <div class="blog-post-image no-image"></div>
                    <?php endif; ?>
                    <div class="blog-post-content">
                        <span class="blog-post-category"><?php echo htmlspecialchars($post['category'] ?? 'General'); ?></span>
                        <h3 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="blog-post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <?php endif; ?>
                        <div class="blog-post-meta">
                            <span class="blog-post-date"><?php echo date('d M Y', strtotime($post['created'])); ?></span>
                            <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>"class="btn btn-primary">
                                <?php echo __('product.learn_more'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <p><?php echo __('homepage.no_blog_posts'); ?></p>
                    <a href="/admin/index.php?section=posts"class="btn btn-primary"><?php echo __('homepage.write_first_post'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>. Всички права запазени.</p>
    </footer>
    
    <!-- Mobile Menu Script -->\n    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
                this.classList.toggle('active');
                document.body.classList.toggle('mobile-menu-open');
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-buttons')) {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    document.body.classList.remove('mobile-menu-open');
                }
            });
            
            // Close mobile menu on link click
            mainNav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    document.body.classList.remove('mobile-menu-open');
                });
            });
        }
    </script>
    
    <!-- Deferred and minified scripts for optimal performance -->
    <script src="assets/js/theme-manager.min.js"defer></script>
</body>
</html>

