<?php
/**
 * Shop Homepage - Product Listing
 */

// Load language system
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/icons.php';

// Get products
$products = load_json('storage/products.json');
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
$categories = load_json('storage/categories.json');
$activeCategories = array_filter($categories, function($cat) {
    return $cat['active'] ?? true;
});
usort($activeCategories, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_option('site_title', __('site_name'))); ?></title>
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></h1>
            </div>
            <div class="nav-buttons">
                <button type="button" id="themeToggle" class="theme-toggle" title="<?php echo __('theme.switch'); ?>">
                    🌙
                </button>
                <a href="?lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language">
                    <?php echo strtoupper(opposite_lang()); ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <span class="user-info"><?php echo htmlspecialchars($user_name); ?></span>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/index.php" class="btn btn-primary">Админ</a>
                    <?php endif; ?>
                    <a href="cart.php" class="btn btn-cart">
                        Количка
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="btn btn-primary">Вход</a>
                    <a href="cart.php" class="btn btn-cart">
                        Количка
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="hero">
        <h2>Добре дошли в <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>!</h2>
        <p>Открийте нашата уникална колекция от ръчно изработени продукти</p>
    </div>

    <?php if (!empty($activeCategories)): ?>
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Категории</h2>
            <div class="categories-grid">
                <?php foreach ($activeCategories as $category): 
                    // Get the latest product from this category (from all products, not just the 6 shown)
                    $categoryProducts = array_filter($products, function($product) use ($category) {
                        return ($product['category'] ?? '') === $category['slug'] && 
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
                            <div class="category-preview-image" style="background: linear-gradient(135deg, rgba(107, 70, 193, 0.2) 0%, rgba(124, 58, 237, 0.2) 100%);"></div>
                        <?php endif; ?>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                            <?php if (!empty($category['description'])): ?>
                                <div class="category-desc"><?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)) . (mb_strlen($category['description']) > 50 ? '...' : ''); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="products-section">
        <div class="container">
            <h2 class="section-title">Нови Продукти</h2>
            
            <?php if (empty($published_products)): ?>
                <div class="empty-state">
                    <h3>Все още няма продукти</h3>
                    <p>Скоро ще добавим красиви изделия!</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($published_products as $product): 
                        $stock = $product['stock'] ?? 0;
                        $price_eur = $product['price'] ?? 0;
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div style="font-size: 80px; color: #c4b5d5;"></div>
                                <?php endif; ?>
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-badge"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php endif; ?>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description">
                                    <?php 
                                    $desc = $product['description'] ?? 'Красив продукт';
                                    echo htmlspecialchars(mb_substr($desc, 0, 120));
                                    if (mb_strlen($desc) > 120) echo '...';
                                    ?>
                                </p>
                                
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
    
    <script src="assets/js/theme.js"></script>
</body>
</html>

