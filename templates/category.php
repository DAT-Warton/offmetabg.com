<?php
/**
 * Category Template - Display Products by Category
 */

require_once CMS_ROOT . '/includes/language.php';
require_once CMS_ROOT . '/includes/icons.php';

// Load products
$products = get_products_data();

// Get category slug for comparison
$categorySlug = strtolower(trim($category['slug']));
$categoryName = strtolower(trim($category['name'] ?? ''));

// Filter products by category - match product category with category slug
$categoryProducts = array_filter($products, function($product) use ($categorySlug, $categoryName) {
    $productCategory = strtolower(trim($product['category'] ?? ''));
    if ($productCategory === '') {
        return false;
    }
    return in_array($productCategory, [$categorySlug, $categoryName], true) &&
           ($product['status'] ?? 'published') === 'published';
});

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

$pageTitle = htmlspecialchars($category['name']);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/category.css">
    <?php echo get_custom_theme_css(); ?>
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="/" class="logo"><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></a>
            
            <nav class="nav-buttons">
                <button type="button" onclick="toggleTheme()" class="theme-toggle" title="<?php echo __('theme.switch'); ?>">
                    <span id="theme-icon"><?php echo icon_moon(18); ?></span>
                </button>
                
                <?php if ($is_logged_in): ?>
                    <a href="/cart.php" class="btn btn-primary">
                        <?php echo icon_cart(18); ?> <?php echo __('cart_button'); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="/auth.php?logout=1" class="btn btn-secondary"><?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="/auth.php?action=login" class="btn btn-secondary"><?php echo __('login'); ?></a>
                    <a href="/auth.php?action=register" class="btn btn-primary"><?php echo __('register'); ?></a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Category Header -->
        <div class="category-header">
            <div class="breadcrumb">
                <a href="/"><?php echo __('home'); ?></a>
                <span>›</span>
                <span><?php echo __('categories'); ?></span>
                <span>›</span>
                <span><?php echo htmlspecialchars($category['name']); ?></span>
            </div>
            
            <h1>
                <?php if (!empty($category['icon'])): ?>
                    <span><?php echo $category['icon']; ?></span>
                <?php endif; ?>
                <?php echo htmlspecialchars($category['name']); ?>
            </h1>
            
            <?php if (!empty($category['description'])): ?>
                <p class="category-description"><?php echo nl2br(htmlspecialchars($category['description'])); ?></p>
            <?php endif; ?>
            
            <p class="product-count">
                <?php echo count($categoryProducts); ?> <?php echo count($categoryProducts) === 1 ? __('product.product') : __('products'); ?>
            </p>
        </div>

        <!-- Products Grid -->
        <?php if (empty($categoryProducts)): ?>
            <div class="no-products">
                <div class="no-products-icon"><?php echo icon_package(64, '#9ca3af'); ?></div>
                <h2><?php echo __('no_products_in_category'); ?></h2>
                <p class="no-products-description">
                    <?php echo __('check_back_later'); ?>
                </p>
                <a href="/" class="btn btn-primary"><?php echo __('back_to_home'); ?></a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($categoryProducts as $id => $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image product-image-placeholder">
                                <?php echo icon_package(48, '#9ca3af'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                
                                <?php if ($is_logged_in): ?>
                                    <form method="POST" action="/cart.php" class="inline-form">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                                        <button type="submit" class="add-to-cart">
                                            <?php echo icon_cart(16); ?> <?php echo __('add_to_cart'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="/auth.php?action=login" class="btn btn-primary btn-small">
                                        <?php echo __('login_to_buy'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/theme-manager.js"></script>
</body>
</html>

