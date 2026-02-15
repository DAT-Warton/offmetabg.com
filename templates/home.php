<?php
/**
 * Shop Homepage - Product Listing
 */

// Load language system
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/icons.php';

// Get products
$products = get_products_data();
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

// Get categories
$categories = get_categories_data();
$activeCategories = array_filter($categories, function($cat) {
    return $cat['active'] ?? true;
});
usort($activeCategories, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

// Get latest blog post
$posts = get_posts();
$published_posts = array_filter($posts, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});
usort($published_posts, function($a, $b) {
    $dateA = $a['created'] ?? '2000-01-01';
    $dateB = $b['created'] ?? '2000-01-01';
    return strtotime($dateB) - strtotime($dateA);
});
$latest_post = !empty($published_posts) ? reset($published_posts) : null;

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    <meta property="og:title" content="<?php echo $site_title; ?>">
    <meta property="og:description" content="<?php echo $site_description; ?>">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/profile-dropdown.css">
    <?php echo get_custom_theme_css(); ?>
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <header>
        <div class="header-container">
            <div class="logo" style="justify-content: <?php echo get_site_setting('logo_position', 'left'); ?>;">
                <?php 
                $logo_url = get_site_setting('logo_url', '');
                $logo_max_height = get_site_setting('logo_max_height', '50');
                $logo_max_width = get_site_setting('logo_max_width', '200');
                $show_site_name = get_site_setting('show_site_name', 'true') === 'true';
                
                if (!empty($logo_url)): 
                ?>
                    <img src="<?php echo htmlspecialchars($logo_url); ?>" 
                         alt="<?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>"
                         style="max-height: <?php echo intval($logo_max_height); ?>px; max-width: <?php echo intval($logo_max_width); ?>px; object-fit: contain;">
                <?php endif; ?>
                
                <?php if ($show_site_name || empty($logo_url)): ?>
                    <h1><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></h1>
                <?php endif; ?>
            </div>
            <div class="nav-buttons">
                <a href="/blog" class="btn btn-secondary"><?php echo __('menu.blog_posts'); ?></a>
                <a href="/about" class="btn btn-secondary"><?php echo __('about_link'); ?></a>
                <a href="?lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language" aria-label="Switch Language">
                    <?php echo strtoupper(opposite_lang()); ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <?php require_once __DIR__ . '/../includes/profile-dropdown.php'; ?>
                    <a href="cart.php" class="btn btn-cart">
                        <?php echo __('cart_button'); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="btn btn-primary"><?php echo __('login'); ?></a>
                    <a href="cart.php" class="btn btn-cart">
                        <?php echo __('cart_button'); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="hero">
        <div class="hero-inner">
            <p class="hero-kicker"><?php echo __('homepage.hero_kicker'); ?></p>
            <p class="hero-subtitle"><?php echo __('homepage.hero_subtitle'); ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary hero-social" href="https://www.facebook.com/offmetabg" target="_blank" rel="noopener noreferrer" title="Facebook">
                    📘 Facebook
                </a>
                <a class="btn btn-primary hero-social" href="https://www.instagram.com/offmetabg" target="_blank" rel="noopener noreferrer" title="Instagram">
                    📷 Instagram
                </a>
                <a class="btn btn-primary hero-social" href="https://www.tiktok.com/@offmetabg" target="_blank" rel="noopener noreferrer" title="TikTok">
                    🎵 TikTok
                </a>
            </div>
            <p class="hero-meta"><?php echo __('homepage.hero_meta'); ?></p>
        </div>
    </div>

    <?php if (!empty($activeCategories)): ?>
    <section class="categories-section" id="categories">
        <div class="container">
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
                    <a href="/category/<?php echo urlencode($category['slug']); ?>" class="category-card">
                        <?php if ($sampleProduct && !empty($sampleProduct['image'])): ?>
                            <div class="category-preview-image" style="background-image: url('<?php echo htmlspecialchars($sampleProduct['image']); ?>');"></div>
                        <?php else: ?>
                            <div class="category-preview-image no-image"></div>
                        <?php endif; ?>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="products-section" id="products">
        <div class="container">
            <h2 class="section-title"><?php echo __('homepage.new_products'); ?></h2>
            
            <?php if ($latest_post): ?>
                <div class="latest-post-card" style="margin-bottom: 2rem; padding: 1.5rem; border-radius: 8px; background: var(--card-bg, #fff); box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
                        <?php if (!empty($latest_post['featured_image'])): ?>
                            <div style="flex: 0 0 200px;">
                                <img src="<?php echo htmlspecialchars($latest_post['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($latest_post['title']); ?>"
                                     style="width: 100%; height: auto; border-radius: 6px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1; min-width: 250px;">
                            <div style="display: inline-block; padding: 0.25rem 0.75rem; background: var(--primary-color, #3b82f6); color: white; border-radius: 4px; font-size: 0.875rem; margin-bottom: 0.75rem;">
                                📝 <?php echo htmlspecialchars($latest_post['category'] ?? 'Блог'); ?>
                            </div>
                            <h3 style="margin: 0 0 0.75rem 0; font-size: 1.5rem; color: var(--text-color, #1f2937);">
                                <?php echo htmlspecialchars($latest_post['title']); ?>
                            </h3>
                            <?php if (!empty($latest_post['excerpt'])): ?>
                                <p style="margin: 0 0 1rem 0; color: var(--text-secondary, #6b7280); line-height: 1.6;">
                                    <?php echo htmlspecialchars(substr($latest_post['excerpt'], 0, 200)); ?><?php echo strlen($latest_post['excerpt']) > 200 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <span style="color: var(--text-secondary, #6b7280); font-size: 0.875rem;">
                                    <?php 
                                        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $latest_post['created']);
                                        echo $dt ? $dt->format('d/m/Y H:i') : date('d/m/Y', strtotime($latest_post['created'])); 
                                    ?>
                                </span>
                                <a href="/blog/<?php echo urlencode($latest_post['slug']); ?>" class="btn btn-primary">
                                    📖 <?php echo __('product.learn_more'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
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
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <?php if (!empty($product['slug'])): ?>
                                    <a href="/product/<?php echo urlencode($product['slug']); ?>" class="btn-learn-more">
                                        📖 <?php echo __('product.learn_more'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <div class="product-footer">
                                    <div>
                                        <div class="product-price">
                                            <?php echo number_format($price_eur, 2); ?> <span class="currency">€</span>
                                        </div>
                                        <?php if ($stock > 0): ?>
                                            <div class="stock-indicator <?php echo $stock <= 5 ? 'low' : ''; ?>">
                                                ✓ <?php echo $stock <= 5 ? "Само $stock бр." : "В наличност"; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="stock-indicator out">✗ Изчерпан</div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <button type="submit" class="btn-buy" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                            🛒 Купи
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($latest_post): ?>
    <section class="latest-post-section">
        <div class="container">
            <h2 class="section-title"><?php echo __('blog'); ?></h2>
            <div class="latest-post-card">
                <?php if (!empty($latest_post['featured_image'])): ?>
                    <div class="post-image">
                        <img src="<?php echo htmlspecialchars($latest_post['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($latest_post['title']); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="post-content">
                    <span class="post-category"><?php echo htmlspecialchars($latest_post['category'] ?? 'General'); ?></span>
                    <h3 class="post-title"><?php echo htmlspecialchars($latest_post['title']); ?></h3>
                    <?php if (!empty($latest_post['excerpt'])): ?>
                        <p class="post-excerpt"><?php echo htmlspecialchars($latest_post['excerpt']); ?></p>
                    <?php endif; ?>
                    <div class="post-meta">
                        <span class="post-date"><?php echo date('d M Y', strtotime($latest_post['created'])); ?></span>
                        <a href="/blog/<?php echo urlencode($latest_post['slug']); ?>" class="btn btn-primary">
                            <?php echo __('product.learn_more'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>. Всички права запазени.</p>
    </footer>
    
    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

