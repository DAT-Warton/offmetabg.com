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
                    <span class="user-info"><?php echo htmlspecialchars($user_name); ?></span>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/index.php" class="btn btn-primary"><?php echo __('admin_panel'); ?></a>
                    <?php endif; ?>
                    <a href="cart.php" class="btn btn-cart">
                        <?php echo __('cart_button'); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/auth.php?logout=1" class="btn btn-secondary"><?php echo __('logout'); ?></a>
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
            <h2><?php echo __('homepage.hero_title'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>!</h2>
            <p class="hero-subtitle"><?php echo __('homepage.hero_subtitle'); ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary hero-cta" href="#products"><?php echo __('homepage.hero_cta_products'); ?></a>
                <a class="btn btn-secondary hero-cta" href="#categories"><?php echo __('homepage.hero_cta_categories'); ?></a>
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
                            <?php if (!empty($category['description'])): ?>
                                <div class="category-desc"><?php echo htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : ''); ?></div>
                            <?php endif; ?>
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
                                <div class="product-description-wrapper">
                                    <p class="product-description">
                                        <?php 
                                        $desc = $product['description'] ?? 'Красив продукт';
                                        echo htmlspecialchars($desc);
                                        ?>
                                    </p>
                                    <?php if (strlen($desc) > 120): ?>
                                        <button class="btn-read-more" onclick="toggleDescription(this)">
                                            <span class="read-more-text">Прочети още</span>
                                            <span class="read-less-text" style="display: none;">Прочети по-малко</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
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

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>. Всички права запазени.</p>
    </footer>
    
    <script src="assets/js/products.js"></script>
    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

