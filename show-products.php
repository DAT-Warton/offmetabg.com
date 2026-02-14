<?php
/**
 * Show first 5 products with their full data
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$products = get_products_data();

echo "First 5 products:\n\n";

$count = 0;
foreach ($products as $product) {
    echo "Product ID: {$product['id']}\n";
    echo "  Name: {$product['name']}\n";
    echo "  Category: '{$product['category']}'\n";
    echo "  Status: {$product['status']}\n";
    echo "  Image: " . (empty($product['image']) ? 'EMPTY' : substr($product['image'], 0, 60) . '...') . "\n";
    echo "\n";
    
    $count++;
    if ($count >= 5) break;

}
