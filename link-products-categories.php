<?php
/**
 * Link existing products to their categories in product_category_links table
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "Starting product-category linking...\n\n";

if (!db_enabled()) {
    die("Database not enabled. Check your configuration.\n");
}

$pdo = Database::getInstance()->getPDO();

// Get all categories
$stmt = $pdo->query("SELECT id, slug, name FROM categories ORDER BY slug");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($categories) . " categories:\n";
foreach ($categories as $cat) {
    echo "  - {$cat['slug']} ({$cat['name']})\n";
}
echo "\n";

// Get all products from both tables
$stmt = $pdo->query("
    SELECT DISTINCT p.id, 
           COALESCE(pd.name, '') as product_name
    FROM products p
    LEFT JOIN product_descriptions pd ON p.id = pd.product_id AND pd.language_code = 'bg'
    ORDER BY p.id
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($products) . " products\n\n";

// Try to get old category data from products_old table if it exists
$oldCategoryData = [];
try {
    $stmt = $pdo->query("SELECT id, category FROM products_old WHERE category IS NOT NULL AND category != ''");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $oldCategoryData[$row['id']] = strtolower(trim($row['category']));
    }
    echo "Found category data for " . count($oldCategoryData) . " products in products_old\n\n";
} catch (Exception $e) {
    echo "No products_old table found, will try to infer from product names\n\n";
}

$linked = 0;
$skipped = 0;

foreach ($products as $product) {
    $productId = $product['id'];
    $productName = strtolower($product['product_name']);
    
    // Check if already linked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_category_links WHERE product_id = ?");
    $stmt->execute([$productId]);
    if ($stmt->fetchColumn() > 0) {
        echo "  Product {$productId} already linked\n";
        $skipped++;
        continue;
    }
    
    // Try to find category from old data
    $categoryToLink = null;
    
    if (isset($oldCategoryData[$productId])) {
        $oldCategory = $oldCategoryData[$productId];
        
        // Find matching category
        foreach ($categories as $cat) {
            $slug = strtolower($cat['slug']);
            $name = strtolower($cat['name']);
            
            if ($oldCategory === $slug || $oldCategory === $name) {
                $categoryToLink = $cat['id'];
                break;
            }
        }
    }
    
    // If no old data, try to infer from product name
    if (!$categoryToLink) {
        if (stripos($productName, 'картичк') !== false || stripos($productName, 'card') !== false) {
            // Find cards category
            foreach ($categories as $cat) {
                if ($cat['slug'] === 'cards') {
                    $categoryToLink = $cat['id'];
                    break;
                }
            }
        } elseif (stripos($productName, 'орнамент') !== false || stripos($productName, 'ornament') !== false) {
            // Find ornaments category
            foreach ($categories as $cat) {
                if ($cat['slug'] === 'ornaments') {
                    $categoryToLink = $cat['id'];
                    break;
                }
            }
        }
    }
    
    // If still no category, use uncategorized
    if (!$categoryToLink) {
        foreach ($categories as $cat) {
            if ($cat['slug'] === 'uncategorized') {
                $categoryToLink = $cat['id'];
                break;
            }
        }
    }
    
    if ($categoryToLink) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO product_category_links (product_id, category_id, is_primary, sort_order)
                VALUES (?, ?, true, 0)
                ON CONFLICT (product_id, category_id) DO NOTHING
            ");
            $stmt->execute([$productId, $categoryToLink]);
            
            echo "✓ Linked product {$productId} ({$productName}) to category\n";
            $linked++;
        } catch (Exception $e) {
            echo "✗ Error linking product {$productId}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n===================\n";
echo "Summary:\n";
echo "  Linked: {$linked}\n";
echo "  Skipped (already linked): {$skipped}\n";
echo "  Total: " . count($products) . "\n";
echo "===================\n";
