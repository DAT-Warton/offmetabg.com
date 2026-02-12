<?php
/**
 * Category Template - Display Products by Category
 */

require_once CMS_ROOT . '/includes/language.php';
require_once CMS_ROOT . '/includes/icons.php';

// Load products
$products = load_json('storage/products.json');

// Get category name for comparison
$categoryName = $category['name'];
$normalizedCategoryName = mb_strtolower(trim($categoryName));

// Filter products by category - normalize both category names for comparison
$categoryProducts = array_filter($products, function($product) use ($normalizedCategoryName) {
    $productCategory = $product['category'] ?? '';
    $normalizedProductCategory = mb_strtolower(trim($productCategory));
    return $normalizedProductCategory === $normalizedCategoryName && 
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
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary, #f5f7fa);
            color: var(--text-primary, #1f2937);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: var(--bg-secondary, white);
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px var(--shadow, rgba(0,0,0,0.1));
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary, #667eea);
            text-decoration: none;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: var(--bg-tertiary, #6b7280);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow, rgba(0,0,0,0.15));
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        /* Category Header */
        .category-header {
            background: var(--bg-secondary, white);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px var(--shadow, rgba(0,0,0,0.08));
        }

        .category-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary, #1f2937);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-description {
            color: var(--text-secondary, #6b7280);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            align-items: center;
            color: var(--text-secondary, #6b7280);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .breadcrumb a {
            color: var(--primary, #667eea);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: var(--bg-secondary, white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow, rgba(0,0,0,0.08));
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px var(--shadow, rgba(0,0,0,0.12));
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: var(--bg-primary, #f5f7fa);
        }

        .product-info {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary, #1f2937);
        }

        .product-description {
            color: var(--text-secondary, #6b7280);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary, #667eea);
        }

        .add-to-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .add-to-cart:hover {
            transform: scale(1.05);
        }

        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-secondary, white);
            border-radius: 12px;
            box-shadow: 0 2px 8px var(--shadow, rgba(0,0,0,0.08));
        }

        .no-products-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-products h2 {
            color: var(--text-secondary, #6b7280);
            margin-bottom: 1rem;
        }

        /* Theme Toggle */
        .theme-toggle {
            background: var(--bg-tertiary, #e5e7eb);
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
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
                            <span style="background: white; color: #667eea; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 700;">
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
            
            <p style="color: var(--text-secondary, #6b7280); font-size: 0.95rem;">
                <?php echo count($categoryProducts); ?> <?php echo count($categoryProducts) === 1 ? __('product.product') : __('products'); ?>
            </p>
        </div>

        <!-- Products Grid -->
        <?php if (empty($categoryProducts)): ?>
            <div class="no-products">
                <div class="no-products-icon"><?php echo icon_package(64, '#9ca3af'); ?></div>
                <h2><?php echo __('no_products_in_category'); ?></h2>
                <p style="color: var(--text-secondary, #6b7280); margin-bottom: 1.5rem;">
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
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center;">
                                <?php echo icon_package(48, '#9ca3af'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                
                                <?php if ($is_logged_in): ?>
                                    <form method="POST" action="/cart.php" style="margin: 0;">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                                        <button type="submit" class="add-to-cart">
                                            <?php echo icon_cart(16); ?> <?php echo __('add_to_cart'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="/auth.php?action=login" class="btn btn-primary" style="font-size: 0.9rem;">
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

    <script src="/assets/js/theme.js"></script>
</body>
</html>
