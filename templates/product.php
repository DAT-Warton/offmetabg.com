<?php
/**
 * Single Product Template
 */

require_once CMS_ROOT . '/includes/language.php';
require_once CMS_ROOT . '/includes/icons.php';

// Get currency settings from database
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

// Product is loaded in index.php as $product variable

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

$pageTitle = htmlspecialchars($product['name']);
$stock = $product['stock'] ?? 0;
$price = $product['price'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <meta name="description"content="<?php echo htmlspecialchars($product['short_description'] ?? substr($product['description'] ?? '', 0, 160)); ?>">
    <meta property="og:title"content="<?php echo $pageTitle; ?>">
    <meta property="og:description"content="<?php echo htmlspecialchars($product['short_description'] ?? ''); ?>">
    
    <!-- Favicon -->
    <link rel="icon"type="image/svg+xml"href="/favicon.svg">
    <link rel="icon"type="image/x-icon"href="/favicon.ico">
    <?php if (!empty($product['image'])): ?>
        <meta property="og:image"content="<?php echo htmlspecialchars($product['image']); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/themes.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
    <?php echo get_custom_theme_css(); ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="/"class="logo"><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></a>
            
            <nav class="nav-buttons">
                <a href="/"class="btn btn-secondary"><?php echo __('home'); ?></a>
                
                <?php if ($is_logged_in): ?>
                    <a href="/cart.php"class="btn btn-primary">
                        <?php echo icon_cart(18); ?> <?php echo __('cart_button'); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="/auth.php?action=login"class="btn btn-secondary"><?php echo __('login'); ?></a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container product-page">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/"><?php echo __('home'); ?></a>
            <span>›</span>
            <?php if (!empty($product['category'])): ?>
                <a href="/category/<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars(ucfirst($product['category'])); ?></a>
                <span>›</span>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Product Details -->
        <div class="product-detail">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <?php if (!empty($product['image'])): ?>
                    <div class="main-image">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="mainProductImage">
                    </div>
                <?php else: ?>
                    <div class="main-image no-image">
                        <span>📦</span>
                    </div>
                <?php endif; ?>
                
                <!-- Video Links if available -->
                <?php if (!empty($product['videos']['youtube']) || !empty($product['videos']['tiktok']) || !empty($product['videos']['instagram'])): ?>
                    <div class="product-videos">
                        <?php if (!empty($product['videos']['youtube'])): ?>
                            <a href="<?php echo htmlspecialchars($product['videos']['youtube']); ?>"target="_blank"rel="noopener"class="video-link">
                                YouTube
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($product['videos']['tiktok'])): ?>
                            <a href="<?php echo htmlspecialchars($product['videos']['tiktok']); ?>"target="_blank"rel="noopener"class="video-link">
                                TikTok
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($product['videos']['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($product['videos']['instagram']); ?>"target="_blank"rel="noopener"class="video-link">
                                Instagram
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info-section">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if (!empty($product['sku'])): ?>
                    <div class="product-sku">
                        <span><?php echo __('product.sku'); ?>:</span> <strong><?php echo htmlspecialchars($product['sku']); ?></strong>
                    </div>
                <?php endif; ?>
                
                <!-- Price -->
                <div class="product-price-section">
                    <?php if ($price <= 0): ?>
                        <div class="product-price-large" style="color: #3498db;">
                            📞 Свържете се за цена
                        </div>
                    <?php else: ?>
                        <?php 
                        $dual_price = get_dual_currency_price($price, 'EUR');
                        ?>
                        <div class="product-price-large">
                            <span style="font-weight: 700; font-size: 2em;"><?php echo number_format($dual_price['eur'], 2); ?> €</span>
                            <span style="color: #666; font-size: 1.2em; margin-left: 12px;">(<?php echo number_format($dual_price['bgn'], 2); ?> лв.)</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($product['compare_price']) && $product['compare_price'] > $price): ?>
                        <?php 
                        $dual_compare = get_dual_currency_price($product['compare_price'], 'EUR');
                        ?>
                        <div class="compare-price">
                            <s><?php echo number_format($dual_compare['eur'], 2); ?> € (<?php echo number_format($dual_compare['bgn'], 2); ?> лв.)</s>
                            <span class="discount-badge">
                                -<?php echo round((1 - $price / $product['compare_price']) * 100); ?>%
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Stock Status -->
                <div class="stock-status">
                    <?php if ($stock > 0): ?>
                        <div class="stock-indicator in-stock <?php echo $stock <= 5 ? 'low' : ''; ?>">
                            ✓ <?php echo $stock <= 5 ? "Само $stock бр. в наличност": "В наличност"; ?>
                        </div>
                    <?php else: ?>
                        <div class="stock-indicator out-of-stock">
                            ✗ Изчерпан
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Short Description -->
                <?php if (!empty($product['short_description'])): ?>
                    <div class="product-short-desc">
                        <?php echo render_description($product['short_description']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="product-actions">
                    <!-- Add to Cart -->
                    <form method="POST"action="/cart.php"class="add-to-cart-form">
                        <input type="hidden"name="action"value="add">
                        <input type="hidden"name="product_id"value="<?php echo htmlspecialchars($product['id']); ?>">
                        <button type="submit"class="btn btn-primary btn-large"<?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                            🛒 <?php echo __('product.add_to_cart'); ?>
                        </button>
                    </form>
                    
                    <!-- Add to Wishlist -->
                    <button type="button"
                            class="btn btn-wishlist btn-large"
                            onclick="toggleWishlist('<?php echo htmlspecialchars($product['id']); ?>')"
                            id="wishlist-btn"
                            data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                        <span id="wishlist-icon">🤍</span>
                        <span id="wishlist-text"><?php echo __('product.add_to_wishlist'); ?></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Product Description -->
        <?php if (!empty($product['description'])): ?>
            <div class="product-description-full">
                <h2><?php echo __('product.description'); ?></h2>
                <div class="description-content">
                    <?php echo render_description($product['description']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>. Всички права запазени.</p>
    </footer>
    
    <script src="/assets/js/theme-manager.min.js"defer></script>
    <script src="/assets/js/wishlist.min.js"defer></script>
    <script>
        // Check if product is in wishlist on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkWishlistStatus('<?php echo htmlspecialchars($product['id']); ?>');
        });
    </script>
</body>
</html>
