<?php
/**
 * Check category images debug
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Checking Category Images ===\n\n";

// Get products using the function
$products = get_products_data();

echo "Total products loaded: " . count($products) . "\n\n";

// Get categories
$categories = get_categories_data();
$activeCategories = array_filter($categories, function($cat) {
    return $cat['active'] ?? true;
});

echo "Active categories: " . count($activeCategories) . "\n\n";

foreach ($activeCategories as $category) {
    echo "Category: {$category['name']} ({$category['slug']})\n";
    
    // Filter products for this category
    $categoryProducts = array_filter($products, function($product) use ($category) {
        $productCategory = strtolower(trim($product['category'] ?? ''));
        $categorySlug = strtolower(trim($category['slug'] ?? ''));
        $categoryName = strtolower(trim($category['name'] ?? ''));
        return in_array($productCategory, [$categorySlug, $categoryName], true) &&
               ($product['status'] ?? 'published') === 'published';
    });
    
    echo "  Found " . count($categoryProducts) . " products\n";
    
    if (!empty($categoryProducts)) {
        $sampleProduct = reset($categoryProducts);
        echo "  Sample product: {$sampleProduct['name']}\n";
        echo "  Product category field: '{$sampleProduct['category']}'\n";
        echo "  Product image: " . ($sampleProduct['image'] ? 'YES (' . substr($sampleProduct['image'], 0, 50) . '...)' : 'NO') . "\n";
    }
    
    echo "\n";
}
