<?php
/**
 * Check product_category_links table
 */

require_once __DIR__ . '/includes/database.php';

$pdo = Database::getInstance()->getPDO();

echo "=== Checking product_category_links ===\n\n";

$stmt = $pdo->query("
    SELECT pcl.*, c.slug as cat_slug, c.name as cat_name 
    FROM product_category_links pcl 
    LEFT JOIN categories c ON pcl.category_id = c.id 
    LIMIT 5
");

$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($links) . " links (showing first 5):\n\n";

foreach ($links as $link) {
    echo "Product: {$link['product_id']}\n";
    echo "  Category ID: {$link['category_id']}\n";
    echo "  Category Slug: {$link['cat_slug']}\n";
    echo "  Category Name: {$link['cat_name']}\n";
    echo "  Is Primary: " . ($link['is_primary'] ? 'YES' : 'NO') . "\n";
    echo "\n";
}

echo "\n=== Now testing the actual query ===\n\n";

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        c.slug as category_slug,
        c.name as category_name
    FROM products p
    LEFT JOIN product_category_links pcl ON p.id = pcl.product_id AND pcl.is_primary = true
    LEFT JOIN categories c ON pcl.category_id = c.id
    LIMIT 5
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "Product: {$row['id']}\n";
    echo "  category_slug: " . ($row['category_slug'] ?? 'NULL') . "\n";
    echo "  category_name: " . ($row['category_name'] ?? 'NULL') . "\n";
    echo "\n";
}
